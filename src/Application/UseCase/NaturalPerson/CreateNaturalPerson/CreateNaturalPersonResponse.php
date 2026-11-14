<?php

declare(strict_types=1);

namespace App\Application\UseCase\NaturalPerson\CreateNaturalPerson;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\NaturalPerson\IdentifierNaturalPerson;

final readonly class CreateNaturalPersonResponse implements UseCaseResponseInterface
{
    public function __construct(
        public IdentifierNaturalPerson $readModel,
    ) {}
}
