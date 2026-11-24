<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\InvalidStorageSpaceStatusTransitionException;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;

interface StorageSpaceRepositoryInterface
{
    /**
     * @throws StorageSpaceCodeAlreadyExistsException when the code is already taken, including by a concurrent insert
     */
    public function add(StorageSpace $storageSpace): void;

    /**
     * Enregistre le nouveau statut d'un espace de stockage existant, seulement
     * s'il est toujours dans l'état $expectedStatus, celui à partir duquel la
     * transition a été calculée : de deux transitions concurrentes, seule la
     * première aboutit.
     *
     * @throws InvalidStorageSpaceStatusTransitionException quand le statut a changé depuis la lecture
     */
    public function update(StorageSpace $storageSpace, StorageSpaceStatus $expectedStatus): void;

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
