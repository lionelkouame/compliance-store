<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateJurisdiction;

final readonly class CreateJurisdictionCommand
{
    /**
     * @param list<string> $applicableFrameworks
     */
    public function __construct(
        public string $code,
        public string $label,
        public string $region,
        public ?string $country,
        public ?string $subRegion,
        public array $applicableFrameworks,
        public bool $active = true,
    ) {}
}
