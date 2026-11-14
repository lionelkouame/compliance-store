<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\CreateStorageSpace;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class CreateStorageSpaceRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}
}
