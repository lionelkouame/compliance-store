<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\LegalFramework;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkId;

interface LegalFrameworkRepositoryInterface
{
    public function findById(LegalFrameworkId $id): ?LegalFramework;

    public function findByCode(LegalFrameworkCode $code): ?LegalFramework;

    /**
     * @return list<LegalFramework>
     */
    public function findAll(): array;

    public function existsByCode(LegalFrameworkCode $code): bool;

    public function add(LegalFramework $legalFramework): void;

    /**
     * Persists changes made to an already-managed LegalFramework (e.g. activate/deactivate).
     */
    public function update(LegalFramework $legalFramework): void;
}
