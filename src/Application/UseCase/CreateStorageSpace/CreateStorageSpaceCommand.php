<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateStorageSpace;

final readonly class CreateStorageSpaceCommand
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}
}
