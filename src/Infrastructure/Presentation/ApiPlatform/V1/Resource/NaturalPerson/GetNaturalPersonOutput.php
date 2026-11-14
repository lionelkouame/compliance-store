<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson;

final readonly class GetNaturalPersonOutput
{
    public function __construct(
        public string $identifier,
        public string $gender,
        public string $firstName,
        public string $lastName,
        public string $birthDate,
        public string $createdAt,
    ) {}
}
