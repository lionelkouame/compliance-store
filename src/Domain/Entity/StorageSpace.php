<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpaceId;
use App\Domain\ValueObject\StorageSpaceName;
use App\Domain\ValueObject\StorageSpaceStatus;

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
     */
    public function withStatus(StorageSpaceStatus $status): self
    {
        return new self(
            id: $this->id,
            code: $this->code,
            name: $this->name,
            status: $status,
            createdAt: $this->createdAt,
        );
    }
}
