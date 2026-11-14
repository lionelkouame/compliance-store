<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\ChangeStorageSpaceStatus;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class ChangeStorageSpaceStatusRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $id,
        public string $status,
    ) {}
}
