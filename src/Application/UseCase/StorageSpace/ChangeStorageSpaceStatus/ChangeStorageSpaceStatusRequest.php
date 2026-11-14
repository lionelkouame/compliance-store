<?php

declare(strict_types=1);

namespace App\Application\UseCase\ChangeStorageSpaceStatus;

final readonly class ChangeStorageSpaceStatusCommand
{
    public function __construct(
        public string $id,
        public string $status,
    ) {}
}
