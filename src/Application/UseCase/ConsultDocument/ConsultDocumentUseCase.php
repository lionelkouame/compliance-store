<?php

declare(strict_types=1);

namespace App\Application\UseCase\ConsultDocument;

use App\Domain\Exception\DocumentIntegrityException;
use App\Domain\Exception\DocumentNotFoundException;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\EncryptedPayload;
use App\Domain\ValueObject\FileHash;

/**
 * Reads a document back: ciphertext from storage, data key unwrapped with the
 * master key, then the plaintext is compared with the hash recorded at store
 * time before anything is released. The envelope does not bind the key to
 * the document, so this check is what detects a swapped or altered pair.
 * Every consultation is written to the audit trail.
 */
final readonly class ConsultDocumentUseCase
{
    public function __construct(
        private DocumentRepositoryInterface $documents,
        private StorageGatewayInterface $storage,
        private CipherGatewayInterface $cipher,
        private AuditTrailInterface $auditTrail,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @throws \InvalidArgumentException  when the id is not a valid UUID
     * @throws DocumentNotFoundException
     * @throws DocumentIntegrityException when the decrypted content does not match the stored hash
     * @throws \RuntimeException          when storage or decryption fails
     */
    public function execute(string $id): ConsultedDocument
    {
        $documentId = DocumentId::fromString($id, $this->idValidator);

        $document = $this->documents->findById($documentId);

        if (null === $document) {
            throw new DocumentNotFoundException($documentId);
        }

        $ciphertext = $this->storage->read($documentId);
        $plaintext = $this->cipher->decrypt(new EncryptedPayload($ciphertext), $document->wrappedDataKey());

        if (!FileHash::fromPlaintext($plaintext)->equals($document->fileHash())) {
            throw new DocumentIntegrityException($documentId);
        }

        $this->auditTrail->record(AuditAction::DocumentConsulted, $documentId->value);

        return new ConsultedDocument($document, $plaintext);
    }
}
