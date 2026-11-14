<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

final readonly class CreateHolderOutput
{
    public function __construct(
        public string $identifier,
    ) {}
}
