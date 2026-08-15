<?php

declare(strict_types=1);

namespace App\Application\UseCase\UpdateLegalFrameworkStatus;

final readonly class UpdateLegalFrameworkStatusCommand
{
    public function __construct(
        public string $code,
        public bool $active,
    ) {}
}
