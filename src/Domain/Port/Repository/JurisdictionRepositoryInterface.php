<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Jurisdiction;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionCountry;
use App\Domain\ValueObject\JurisdictionId;
use App\Domain\ValueObject\JurisdictionRegion;

interface JurisdictionRepositoryInterface
{
    public function findById(JurisdictionId $id): ?Jurisdiction;

    public function findByCode(JurisdictionCode $code): ?Jurisdiction;

    /**
     * @return list<Jurisdiction>
     */
    public function findAll(): array;

    /**
     * Finds jurisdictions matching the given optional criteria, evaluated at
     * the persistence layer (not in application memory).
     *
     * `$active` stays a native `bool` per ADR 0004 §5 (self-documenting
     * primitives are exempt from the Value Object rule); `region`/`country`
     * use the same Value Objects as the rest of this port (ADR 0004 §1).
     *
     * @return list<Jurisdiction>
     */
    public function findAllMatching(?JurisdictionRegion $region = null, ?JurisdictionCountry $country = null, ?bool $active = null): array;

    public function existsByCode(JurisdictionCode $code): bool;

    public function add(Jurisdiction $jurisdiction): void;

    /**
     * Persists changes made to an already-managed Jurisdiction (e.g. activate/deactivate).
     */
    public function update(Jurisdiction $jurisdiction): void;
}
