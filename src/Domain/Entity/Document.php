<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Document\WrappedDataKey;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * A sensitive document, stored encrypted (Zero Trust Storage, ADR 0002)
 * after passing the Native Compliance Core check (ADR 0001). Immutable once
 * stored.
 *
 * The document belongs to a Holder (data subject). The relation is carried
 * by the HolderId identifier rather than by an object reference: the Holder
 * aggregate does not hold the collection of its documents.
 */
final class Document
{
    private function __construct(
        private readonly DocumentId $id,
        private readonly HolderId $holderId,
        private readonly FileHash $fileHash,
        private readonly WrappedDataKey $wrappedDataKey,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        DocumentId $id,
        HolderId $holderId,
        FileHash $fileHash,
        WrappedDataKey $wrappedDataKey,
    ): self {
        return new self(
            id: $id,
            holderId: $holderId,
            fileHash: $fileHash,
            wrappedDataKey: $wrappedDataKey,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function id(): DocumentId
    {
        return $this->id;
    }

    public function holderId(): HolderId
    {
        return $this->holderId;
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
