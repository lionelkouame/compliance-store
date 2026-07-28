<?php

namespace App\Domain\Port\Repository;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\ValueObject\RegulatoryScopeCode;

interface RegulatoryScopeRepositoryInterface
{
    public function findByCode(RegulatoryScopeCode $code): ?RegulatoryScope;

    public function findActiveByCode(RegulatoryScopeCode $code): ?RegulatoryScope;

    /**
     * @return list<RegulatoryScope>
     */
    public function findAll(): array;

    public function existsByCode(RegulatoryScopeCode $code): bool;

    public function add(RegulatoryScope $regulatoryScope): void;
}
