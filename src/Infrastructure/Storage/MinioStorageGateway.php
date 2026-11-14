<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\ValueObject\Document\DocumentId;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

/**
 * Zero Trust Storage (ADR 0002) adapter storing already-encrypted document
 * bytes on a MinIO/S3-compatible bucket via Flysystem.
 *
 * The physical object key is derived here from the document id (ADR 0009);
 * it is never modeled in the domain.
 */
final readonly class MinioStorageGateway implements StorageGatewayInterface
{
    public function __construct(
        private FilesystemOperator $documentsStorage,
    ) {}

    public function store(DocumentId $id, string $ciphertext): void
    {
        $key = StorageKey::forDocument($id);

        try {
            $this->documentsStorage->write($key->value, $ciphertext);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(\sprintf('Unable to store the document at "%s": %s', $key->value, $e->getMessage()), previous: $e);
        }
    }

    public function read(DocumentId $id): string
    {
        $key = StorageKey::forDocument($id);

        try {
            return $this->documentsStorage->read($key->value);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(\sprintf('Unable to read the document at "%s".', $key->value), previous: $e);
        }
    }

    public function delete(DocumentId $id): void
    {
        $key = StorageKey::forDocument($id);

        try {
            // Flysystem's delete() does not fail on a missing file: the call is idempotent.
            $this->documentsStorage->delete($key->value);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(\sprintf('Unable to delete the document at "%s".', $key->value), previous: $e);
        }
    }
}
