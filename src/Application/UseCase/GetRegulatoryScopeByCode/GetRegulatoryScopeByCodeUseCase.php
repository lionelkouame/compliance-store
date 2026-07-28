<?php

namespace App\Application\UseCase\GetRegulatoryScopeByCode;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;

final readonly class GetRegulatoryScopeByCodeUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    public function execute(string $code): ?RegulatoryScope
    {
        try {
            $scopeCode = new RegulatoryScopeCode($code);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->regulatoryScopes->findByCode($scopeCode);
    }
}
