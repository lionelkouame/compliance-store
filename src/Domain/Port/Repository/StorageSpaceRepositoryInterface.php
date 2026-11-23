<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;

interface StorageSpaceRepositoryInterface
{
    /**
     * @throws StorageSpaceCodeAlreadyExistsException when the code is already taken, including by a concurrent insert
     */
    public function add(StorageSpace $storageSpace): void;

    public function update(StorageSpace $storageSpace): void;

    /**
     * @throws StorageSpaceNotFoundException when no storage space has this id
     */
    public function getById(StorageSpaceId $id): StorageSpace;

    public function findByCode(StorageSpaceCode $code): ?StorageSpace;

    /**
     * @return list<StorageSpace>
     */
    public function findAll(): array;
}
