<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\StorageSpace\InvalidStorageSpaceStatusTransitionException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;

final readonly class StorageSpace
{
    public function __construct(
        public StorageSpaceId $id,
        public StorageSpaceCode $code,
        public StorageSpaceName $name,
        public StorageSpaceStatus $status,
        public \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        StorageSpaceId $id,
        StorageSpaceCode $code,
        StorageSpaceName $name,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: $name,
            status: StorageSpaceStatus::Active,
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    /**
     * Properties are readonly, so a status change is a new instance rather
     * than a mutation: the repository is responsible for persisting it.
     *
     * @throws InvalidStorageSpaceStatusTransitionException when the lifecycle forbids it
     *                                                      (see {@see StorageSpaceStatus})
     */
    public function withStatus(StorageSpaceStatus $status): self
    {
        if (!$this->status->canTransitionTo($status)) {
            throw new InvalidStorageSpaceStatusTransitionException($this->status, $status);
        }

        return new self(
            id: $this->id,
            code: $this->code,
            name: $this->name,
            status: $status,
            createdAt: $this->createdAt,
        );
    }
}
