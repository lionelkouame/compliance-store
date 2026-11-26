<?php

declare(strict_types=1);

namespace App\Domain\Event\StorageSpace;

use App\Domain\Entity\StorageSpace;
use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Shared\Identifier\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;

/**
 * Raised once a new {@see StorageSpace} has been created and persisted.
 *
 * Carries only the data a subscriber needs (identifier, code, timestamp),
 * not the aggregate, so it remains immutable and serializable.
 */
final readonly class StorageSpaceCreated implements DomainEvent
{
    public function __construct(
        private StorageSpaceId $storageSpaceId,
        private StorageSpaceCode $code,
        private \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Convenience factory building the event from the aggregate itself.
     */
    public static function fromStorageSpace(StorageSpace $storageSpace): self
    {
        return new self(
            $storageSpace->id,
            $storageSpace->code,
            $storageSpace->createdAt,
        );
    }

    public function storageSpaceId(): StorageSpaceId
    {
        return $this->storageSpaceId;
    }

    public function code(): StorageSpaceCode
    {
        return $this->code;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
