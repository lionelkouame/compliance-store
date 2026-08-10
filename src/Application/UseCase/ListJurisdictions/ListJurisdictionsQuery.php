<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListJurisdictions;

final readonly class ListJurisdictionsQuery
{
    public function __construct(
        public ?string $region = null,
        public ?string $country = null,
        public ?bool $active = null,
    ) {}
}
