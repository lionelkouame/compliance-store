<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\NaturalPerson;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

interface NaturalPersonRepositoryInterface
{
    public function findById(NaturalPersonId $id): ?NaturalPerson;

    public function add(NaturalPerson $naturalPerson): void;
}
