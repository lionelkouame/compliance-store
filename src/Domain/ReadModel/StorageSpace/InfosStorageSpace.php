<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\StorageSpace;

use App\Domain\Entity\StorageSpace;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;

/**
 * Read-only projection of a StorageSpace, shared by every StorageSpace use case.
 */
final readonly class InfosStorageSpace
{
    public function __construct(
        public StorageSpaceId $id,
        public StorageSpaceCode $code,
        public StorageSpaceName $name,
        public StorageSpaceStatus $status,
        public \DateTimeImmutable $createdAt,
    ) {}

    public static function fromEntity(StorageSpace $storageSpace): self
    {
        return new self(
            id: $storageSpace->id,
            code: $storageSpace->code,
            name: $storageSpace->name,
            status: $storageSpace->status,
            createdAt: $storageSpace->createdAt,
        );
    }
}
