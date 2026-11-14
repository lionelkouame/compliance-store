<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\NaturalPerson;

use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

final readonly class IdentifierNaturalPerson
{
    public function __construct(
        public NaturalPersonId $id,
    ) {}
}
