<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\NaturalPerson;

use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

readonly class CreateNaturalPersonRead
{
    public function __construct(
        public NaturalPersonId $id,
    ) {}
}
