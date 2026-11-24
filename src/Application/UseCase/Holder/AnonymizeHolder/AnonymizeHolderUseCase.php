<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\AnonymizeHolder;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Event\Holder\HolderAnonymized;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Port\Event\DomainEventPublisher;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Persistence\TransactionInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\Holder\InfosHolder;
use App\Domain\ValueObject\Shared\Identifier\HolderId;

/**
 * Exécute l'effacement d'un titulaire (RGPD art. 17) : supprime ses
 * documents, puis remplace ses données personnelles ({@see \App\Domain\Entity\Holder::anonymize()}).
 *
 * Ordre des opérations :
 * 1. la transition est calculée en premier : un titulaire sans demande
 *    d'effacement est refusé avant toute suppression ;
 * 2. le contenu chiffré de chaque document est supprimé du stockage objet,
 *    hors transaction : le stockage n'y participe pas, et un appel réseau ne
 *    doit pas garder une transaction ouverte ;
 * 3. les lignes des documents (qui portent les clés de données chiffrées) et
 *    le titulaire anonymisé sont écrits dans une seule transaction : tout ou
 *    rien.
 *
 * Si l'étape 3 échoue, le titulaire reste en « erasure_requested » avec des
 * documents dont le contenu a déjà disparu : une nouvelle tentative termine
 * le travail, la suppression dans le stockage étant idempotente. Si deux
 * anonymisations sont concurrentes, la seconde est refusée par l'UPDATE
 * conditionnel et sa transaction est annulée.
 *
 * @implements UseCaseInterface<AnonymizeHolderRequest, AnonymizeHolderResponse>
 */
final readonly class AnonymizeHolderUseCase implements UseCaseInterface
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private DocumentRepositoryInterface $documents,
        private StorageGatewayInterface $storage,
        private TransactionInterface $transaction,
        private IdValidatorInterface $idValidator,
        private DomainEventPublisher $domainEventPublisher,
    ) {}

    /**
     * @throws HolderNotFoundException                quand l'identifiant est malformé ou ne correspond à aucun titulaire
     * @throws InvalidHolderStatusTransitionException quand aucune demande d'effacement n'est en cours, ou qu'une anonymisation concurrente est passée avant
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): AnonymizeHolderResponse
    {
        // Un identifiant qui n'est même pas un UUID valide ne peut rien désigner : c'est un « non trouvé » (ADR 0013).
        if (!$this->idValidator->isValid($registerIdentityDocumentRequest->id)) {
            throw new HolderNotFoundException();
        }

        $holder = $this->holders->findById(HolderId::make($registerIdentityDocumentRequest->id)) // @phpstan-ignore missingType.checkedException (identifiant validé ci-dessus)
            ?? throw new HolderNotFoundException();

        $anonymized = $holder->anonymize();

        $documents = $this->documents->findByHolder($holder->id());

        foreach ($documents as $document) {
            $this->storage->delete($document->id());
        }

        $this->transaction->run(function () use ($documents, $anonymized, $holder): void {
            foreach ($documents as $document) {
                $this->documents->remove($document);
            }

            // Statut lu en base : l'écriture est refusée si une autre anonymisation est passée entre-temps.
            $this->holders->update($anonymized, expectedStatus: $holder->status());
        });

        $this->domainEventPublisher->publish(HolderAnonymized::fromHolder($anonymized));

        return new AnonymizeHolderResponse(InfosHolder::fromEntity($anonymized));
    }
}
