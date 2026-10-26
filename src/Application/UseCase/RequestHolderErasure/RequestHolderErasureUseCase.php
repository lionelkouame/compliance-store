<?php

declare(strict_types=1);

namespace App\Application\UseCase\RequestHolderErasure;

use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Exception\InvalidHolderStatusTransitionException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\HolderId;

/**
 * GDPR art. 17, step 1: the data subject asks for erasure. Nothing is
 * destroyed yet; the holder is flagged so that {@see AnonymizeHolderUseCase}
 * can carry the request out once it has been reviewed.
 */
final readonly class RequestHolderErasureUseCase
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private IdValidatorInterface $idValidator,
        private AuditTrailInterface $auditTrail,
    ) {}

    /**
     * @throws \InvalidArgumentException                when the id is not a valid UUID
     * @throws HolderNotFoundException
     * @throws InvalidHolderStatusTransitionException when the holder is not active
     */
    public function execute(string $id): Holder
    {
        $holderId = HolderId::fromString($id, $this->idValidator);

        $holder = $this->holders->findById($holderId);

        if (null === $holder) {
            throw new HolderNotFoundException($holderId);
        }

        $flagged = $holder->requestErasure();

        $this->holders->update($flagged);

        $this->auditTrail->record(AuditAction::HolderErasureRequested, $holderId->value);

        return $flagged;
    }
}
