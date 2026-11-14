<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\NaturalPerson;

use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\NaturalPerson\FirstName;
use App\Domain\ValueObject\NaturalPerson\Gender;
use App\Domain\ValueObject\NaturalPerson\LastName;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

final readonly class InfosNaturalPerson
{
    public function __construct(
        public NaturalPersonId $id,
        public Gender $gender,
        public FirstName $firstName,
        public LastName $lastName,
        public BirthDate $birthDate,
        public \DateTimeImmutable $createdAt,
    ) {}
}
