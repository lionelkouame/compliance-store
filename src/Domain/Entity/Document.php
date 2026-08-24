<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\DocumentMetadata;
use App\Domain\ValueObject\FileHash;
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\StorageKey;
use App\Domain\ValueObject\WrappedDataKey;

/**
 * A sensitive document, stored encrypted (Zero Trust Storage, ADR 0002)
 * after passing the Native Compliance Core check (ADR 0001). Immutable once
 * stored.
 */
final class Document
{
    private function __construct(
        private readonly DocumentId $id,
        private readonly OwnerId $ownerId,
        private readonly DocumentMetadata $metadata,
        private readonly FileHash $fileHash,
        private readonly WrappedDataKey $wrappedDataKey,
        private readonly StorageKey $storageKey,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        DocumentId $id,
        OwnerId $ownerId,
        DocumentMetadata $metadata,
        FileHash $fileHash,
        WrappedDataKey $wrappedDataKey,
        StorageKey $storageKey,
    ): self {
        return new self(
            id: $id,
            ownerId: $ownerId,
            metadata: $metadata,
            fileHash: $fileHash,
            wrappedDataKey: $wrappedDataKey,
            storageKey: $storageKey,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function id(): DocumentId
    {
        return $this->id;
    }

    public function ownerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function metadata(): DocumentMetadata
    {
        return $this->metadata;
    }

    public function fileHash(): FileHash
    {
        return $this->fileHash;
    }

    public function wrappedDataKey(): WrappedDataKey
    {
        return $this->wrappedDataKey;
    }

    public function storageKey(): StorageKey
    {
        return $this->storageKey;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
