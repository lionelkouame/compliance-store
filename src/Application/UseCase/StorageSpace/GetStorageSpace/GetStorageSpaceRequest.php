<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\GetStorageSpace;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class GetStorageSpaceRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $id,
    ) {}
}
