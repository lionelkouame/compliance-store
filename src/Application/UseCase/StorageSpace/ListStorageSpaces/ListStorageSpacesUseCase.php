<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\ListStorageSpaces;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\StorageSpace;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;

/**
 * @implements UseCaseInterface<ListStorageSpacesRequest, ListStorageSpacesResponse>
 */
final readonly class ListStorageSpacesUseCase implements UseCaseInterface
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
    ) {}

    public function execute(UseCaseRequestInterface $request): ListStorageSpacesResponse
    {
        return new ListStorageSpacesResponse(array_map(
            static fn (StorageSpace $storageSpace): InfosStorageSpace => InfosStorageSpace::fromEntity($storageSpace),
            $this->storageSpaces->findAll(),
        ));
    }
}
