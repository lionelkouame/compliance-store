<?php

declare(strict_types=1);

namespace App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class GetInformationNaturalPersonRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $identifier,
    ) {}
}
