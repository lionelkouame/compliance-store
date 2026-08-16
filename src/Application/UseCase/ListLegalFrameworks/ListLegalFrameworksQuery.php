<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListLegalFrameworks;

final readonly class ListLegalFrameworksQuery
{
    public function __construct(
        public ?string $jurisdictionCode = null,
        public ?bool $active = null,
    ) {}
}
