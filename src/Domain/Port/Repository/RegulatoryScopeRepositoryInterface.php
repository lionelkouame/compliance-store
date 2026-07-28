<?php

namespace App\Domain\Port\Repository;

use App\Domain\Entity\RegulatoryScope;

interface RegulatoryScopeRepositoryInterface
{
    public function findByCode(string $code): ?RegulatoryScope;

    public function findActiveByCode(string $code): ?RegulatoryScope;

    /**
     * @return list<RegulatoryScope>
     */
    public function findAll(): array;

    public function existsByCode(string $code): bool;

    public function add(RegulatoryScope $regulatoryScope): void;
}
