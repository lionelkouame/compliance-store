<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\StorageSpace;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;

interface StorageSpaceRepositoryInterface
{
    public function save(StorageSpace $storageSpace): void;

    public function update(StorageSpace $storageSpace): void;

    public function get(StorageSpaceId $id): ?StorageSpace;

    public function findByCode(StorageSpaceCode $code): ?StorageSpace;

    /**
     * @return list<StorageSpace>
     */
    public function findAll(): array;
}
