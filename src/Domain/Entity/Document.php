<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\FileHash;
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
        private readonly FileHash $fileHash,
        private readonly WrappedDataKey $wrappedDataKey,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        DocumentId $id,
        FileHash $fileHash,
        WrappedDataKey $wrappedDataKey,
    ): self {
        return new self(
            id: $id,
            fileHash: $fileHash,
            wrappedDataKey: $wrappedDataKey,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function id(): DocumentId
    {
        return $this->id;
    }

    public function fileHash(): FileHash
    {
        return $this->fileHash;
    }

    public function wrappedDataKey(): WrappedDataKey
    {
        return $this->wrappedDataKey;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
