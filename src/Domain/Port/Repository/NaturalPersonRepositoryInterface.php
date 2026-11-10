<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\NaturalPerson;

interface NaturalPersonRepositoryInterface
{
    public function findById(string $id): ?NaturalPerson;

    public function add(NaturalPerson $naturalPerson): void;
}
