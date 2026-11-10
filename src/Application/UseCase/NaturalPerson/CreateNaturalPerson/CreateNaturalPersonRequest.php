<?php

declare(strict_types=1);

namespace App\Application\UseCase\NaturalPerson\CreateNaturalPerson;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class CreateNaturalPersonRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $birthday,
        public ?string $gender = null,
    ) {}
}
