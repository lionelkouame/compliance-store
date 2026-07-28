<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeId;

interface RegulatoryScopeRepositoryInterface
{
    public function findById(RegulatoryScopeId $id): ?RegulatoryScope;

    public function findByCode(RegulatoryScopeCode $code): ?RegulatoryScope;

    public function findActiveByCode(RegulatoryScopeCode $code): ?RegulatoryScope;

    /**
     * @return list<RegulatoryScope>
     */
    public function findAll(): array;

    public function existsById(RegulatoryScopeId $id): bool;

    public function existsByCode(RegulatoryScopeCode $code): bool;

    public function add(RegulatoryScope $regulatoryScope): void;
}
