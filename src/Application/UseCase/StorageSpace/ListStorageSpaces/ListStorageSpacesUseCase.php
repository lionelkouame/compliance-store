<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListStorageSpaces;

use App\Domain\Entity\StorageSpace;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;

final readonly class ListStorageSpacesUseCase
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
    ) {}

    /**
     * @return list<StorageSpace>
     */
    public function execute(): array
    {
        return $this->storageSpaces->findAll();
    }
}
