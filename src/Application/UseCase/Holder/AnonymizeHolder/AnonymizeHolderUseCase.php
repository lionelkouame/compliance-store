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
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\Holder\InfosHolder;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * Exécute l'effacement d'un titulaire (RGPD art. 17) : supprime ses
 * documents, puis remplace ses données personnelles ({@see \App\Domain\Entity\Holder::anonymize()}).
 *
 * Ordre des opérations :
 * 1. la transition est calculée en premier : un titulaire sans demande
 *    d'effacement est refusé avant toute suppression ;
 * 2. pour chaque document, le contenu chiffré est supprimé du stockage objet,
 *    puis la ligne (qui porte la clé de données chiffrée). Dans cet ordre, un
 *    échec laisse au pire une ligne sans contenu, et une nouvelle tentative
 *    termine le travail : la suppression dans le stockage est idempotente ;
 * 3. le titulaire n'est enregistré comme anonymisé qu'une fois tous ses
 *    documents supprimés. Tant que ce n'est pas le cas, il reste en
 *    « erasure_requested » et l'opération peut être relancée.
 *
 * @implements UseCaseInterface<AnonymizeHolderRequest, AnonymizeHolderResponse>
 */
final readonly class AnonymizeHolderUseCase implements UseCaseInterface
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private DocumentRepositoryInterface $documents,
        private StorageGatewayInterface $storage,
        private IdValidatorInterface $idValidator,
        private DomainEventPublisher $domainEventPublisher,
    ) {}

    /**
     * @throws HolderNotFoundException                quand l'identifiant est malformé ou ne correspond à aucun titulaire
     * @throws InvalidHolderStatusTransitionException quand aucune demande d'effacement n'est en cours
     */
    public function execute(UseCaseRequestInterface $request): AnonymizeHolderResponse
    {
        // Un identifiant qui n'est même pas un UUID valide ne peut rien désigner : c'est un « non trouvé » (ADR 0013).
        if (!$this->idValidator->isValid($request->id)) {
            throw new HolderNotFoundException();
        }

        $holder = $this->holders->findById(HolderId::make($request->id)) // @phpstan-ignore missingType.checkedException (identifiant validé ci-dessus)
            ?? throw new HolderNotFoundException();

        $anonymized = $holder->anonymize();

        foreach ($this->documents->findByHolder($holder->id()) as $document) {
            $this->storage->delete($document->id());
            $this->documents->remove($document);
        }

        $this->holders->update($anonymized);

        $this->domainEventPublisher->publish(HolderAnonymized::fromHolder($anonymized));

        return new AnonymizeHolderResponse(InfosHolder::fromEntity($anonymized));
    }
}
