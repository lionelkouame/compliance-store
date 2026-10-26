<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\ValueObject\HolderId;

interface HolderRepositoryInterface
{
    public function findById(HolderId $id): ?Holder;

    public function findByEmail(string $email): ?Holder;

    public function add(Holder $holder): void;

    /**
     * Persists a new state of an existing holder (erasure requested, anonymized).
     *
     * @throws HolderNotFoundException when no holder carries this id
     */
    public function update(Holder $holder): void;
}
