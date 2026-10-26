<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

use App\Domain\Entity\Document;
use App\Domain\Exception\HolderNotActiveException;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\DocumentIdGeneratorInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\FileHash;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderStatus;

final readonly class StoreDocumentUseCase
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private HolderRepositoryInterface $holders,
        private DocumentIdGeneratorInterface $idGenerator,
        private IdValidatorInterface $idValidator,
        private AuditTrailInterface $auditTrail,
    ) {}

    /**
     * @throws \InvalidArgumentException when the holder id is not a valid UUID
     * @throws HolderNotFoundException  when the document references an unknown holder
     * @throws HolderNotActiveException when the holder asked for erasure or is anonymized
     */
    public function execute(StoreDocumentCommand $command): Document
    {
        $holderId = HolderId::fromString($command->holderId, $this->idValidator);

        $holder = $this->holders->findById($holderId);

        if (null === $holder) {
            throw new HolderNotFoundException($holderId);
        }

        if (HolderStatus::Active !== $holder->status()) {
            throw new HolderNotActiveException($holderId, $holder->status());
        }

        $fileHash = FileHash::fromPlaintext($command->content);
        $envelope = $this->cipher->encrypt($command->content);

        $id = $this->idGenerator->generate();

        $this->storage->store($id, $envelope->payload->ciphertext);

        $document = Document::create(
            id: $id,
            holderId: $holderId,
            fileHash: $fileHash,
            wrappedDataKey: $envelope->wrappedDataKey,
        );

        try {
            $this->documents->add($document);
        } catch (\Throwable $e) {
            // The ciphertext was written before the record: without the record
            // (and its wrapped key) it is an unreadable orphan, so remove it.
            $this->compensateStorage($id);

            throw $e;
        }

        $this->auditTrail->record(AuditAction::DocumentStored, $id->value);

        return $document;
    }

    private function compensateStorage(DocumentId $id): void
    {
        try {
            $this->storage->delete($id);
        } catch (\Throwable) {
            // Best effort: the original failure is the one worth reporting; the
            // orphan is unreadable anyway since its wrapped key was never persisted.
        }
    }
}
