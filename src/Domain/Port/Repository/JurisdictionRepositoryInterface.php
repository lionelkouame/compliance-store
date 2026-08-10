<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Jurisdiction;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionId;

interface JurisdictionRepositoryInterface
{
    public function findById(JurisdictionId $id): ?Jurisdiction;

    public function findByCode(JurisdictionCode $code): ?Jurisdiction;

    /**
     * @return list<Jurisdiction>
     */
    public function findAll(): array;

    public function existsByCode(JurisdictionCode $code): bool;

    public function add(Jurisdiction $jurisdiction): void;

    /**
     * Persists changes made to an already-managed Jurisdiction (e.g. activate/deactivate).
     */
    public function update(Jurisdiction $jurisdiction): void;
}
