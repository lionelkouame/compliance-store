<?php

declare(strict_types=1);

namespace App\Application\UseCase\UpdateJurisdictionStatus;

final readonly class UpdateJurisdictionStatusCommand
{
    public function __construct(
        public string $code,
        public bool $active,
    ) {}
}
