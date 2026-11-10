<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson;

final class CreateNaturalPersonOutput
{
    public function __construct(
        public string $identifier,
    ) {}
}
