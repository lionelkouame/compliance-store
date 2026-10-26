<?php

declare(strict_types=1);

namespace App\Application\UseCase\AnonymizeHolder;

use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Exception\InvalidHolderStatusTransitionException;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\HolderId;

/**
 * GDPR art. 17, step 2: carries out a pending erasure request.
 *
 * Every document of the holder is destroyed first (ciphertext removed from
 * storage, record and wrapped data key removed from the database: even a
 * forgotten copy of the ciphertext is unreadable), then the holder itself is
 * anonymized. If a deletion fails, the holder stays in ErasureRequested and
 * the operation can simply be replayed.
 */
final readonly class AnonymizeHolderUseCase
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private DocumentRepositoryInterface $documents,
        private StorageGatewayInterface $storage,
        private IdValidatorInterface $idValidator,
        private AuditTrailInterface $auditTrail,
    ) {}

    /**
     * @throws \InvalidArgumentException                when the id is not a valid UUID
     * @throws HolderNotFoundException
     * @throws InvalidHolderStatusTransitionException when no erasure request is pending
     */
    public function execute(string $id): Holder
    {
        $holderId = HolderId::fromString($id, $this->idValidator);

        $holder = $this->holders->findById($holderId);

        if (null === $holder) {
            throw new HolderNotFoundException($holderId);
        }

        // Validate the transition before destroying anything.
        $anonymized = $holder->anonymize();

        foreach ($this->documents->findByHolder($holderId) as $document) {
            $this->storage->delete($document->id());
            $this->documents->remove($document);
        }

        $this->holders->update($anonymized);

        $this->auditTrail->record(AuditAction::HolderAnonymized, $holderId->value);

        return $anonymized;
    }
}
