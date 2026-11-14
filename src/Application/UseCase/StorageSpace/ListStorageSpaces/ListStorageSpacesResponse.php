<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\ListStorageSpaces;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;

final readonly class ListStorageSpacesResponse implements UseCaseResponseInterface
{
    /**
     * @param list<InfosStorageSpace> $readModels
     */
    public function __construct(
        public array $readModels,
    ) {}
}
