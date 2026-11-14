<?php

declare(strict_types=1);

namespace App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\NaturalPerson\InfosNaturalPerson;

final readonly class GetInformationNaturalPersonResponse implements UseCaseResponseInterface
{
    public function __construct(
        public InfosNaturalPerson $readModel,
    ) {}
}
