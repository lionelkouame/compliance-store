<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Holder;

interface HolderRepositoryInterface
{
    public function findById(string $id): ?Holder;

    public function findByEmail(string $email): ?Holder;

    public function add(Holder $holder): void;
}
