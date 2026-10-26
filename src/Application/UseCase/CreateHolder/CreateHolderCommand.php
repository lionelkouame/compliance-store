<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateHolder;

final readonly class CreateHolderCommand
{
    public function __construct(
        public string $holderName,
        public string $birthDate,
        public string $email,
    ) {}
}
