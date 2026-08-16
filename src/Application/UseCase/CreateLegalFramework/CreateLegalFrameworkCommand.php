<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateLegalFramework;

final readonly class CreateLegalFrameworkCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public string $officialReference,
        public string $regulatoryAuthority,
        public string $jurisdictionCode,
        public bool $active = true,
    ) {}
}
