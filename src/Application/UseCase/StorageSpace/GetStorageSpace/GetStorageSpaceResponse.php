<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\GetStorageSpace;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;

final readonly class GetStorageSpaceResponse implements UseCaseResponseInterface
{
    public function __construct(
        public InfosStorageSpace $readModel,
    ) {}
}
