<?php

namespace App\Application\UseCase\GetRegulatoryScopeByCode;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;

final readonly class GetRegulatoryScopeByCodeUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    public function execute(string $code): ?RegulatoryScope
    {
        return $this->regulatoryScopes->findByCode($code);
    }
}
