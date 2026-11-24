<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\RequestHolderErasure;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Event\Holder\HolderErasureRequested;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Port\Event\DomainEventPublisher;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\Holder\InfosHolder;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * Enregistre la demande d'effacement d'un titulaire (RGPD art. 17).
 *
 * L'effacement lui-même est une étape distincte ({@see \App\Application\UseCase\Holder\AnonymizeHolder\AnonymizeHolderUseCase}) :
 * entre les deux, le responsable de traitement peut vérifier qu'aucune
 * obligation légale n'impose de conserver les données.
 *
 * @implements UseCaseInterface<RequestHolderErasureRequest, RequestHolderErasureResponse>
 */
final readonly class RequestHolderErasureUseCase implements UseCaseInterface
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private IdValidatorInterface $idValidator,
        private DomainEventPublisher $domainEventPublisher,
    ) {}

    /**
     * @throws HolderNotFoundException                quand l'identifiant est malformé ou ne correspond à aucun titulaire
     * @throws InvalidHolderStatusTransitionException quand le titulaire n'est plus actif (demande déjà faite, ou déjà anonymisé), y compris par une requête concurrente
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): RequestHolderErasureResponse
    {
        // Un identifiant qui n'est même pas un UUID valide ne peut rien désigner : c'est un « non trouvé » (ADR 0013).
        if (!$this->idValidator->isValid($registerIdentityDocumentRequest->id)) {
            throw new HolderNotFoundException();
        }

        $holder = $this->holders->findById(HolderId::make($registerIdentityDocumentRequest->id)) // @phpstan-ignore missingType.checkedException (identifiant validé ci-dessus)
            ?? throw new HolderNotFoundException();

        $requested = $holder->requestErasure();

        // Statut lu en base : l'écriture est refusée si un autre traitement l'a changé entre-temps.
        $this->holders->update($requested, expectedStatus: $holder->status());

        $this->domainEventPublisher->publish(HolderErasureRequested::fromHolder($requested));

        return new RequestHolderErasureResponse(InfosHolder::fromEntity($requested));
    }
}
