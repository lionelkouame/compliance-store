<?php

namespace App\Application\UseCase\ListRegulatoryScopes;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;

final readonly class ListRegulatoryScopesUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    /**
     * @return list<RegulatoryScope>
     */
    public function execute(): array
    {
        return $this->regulatoryScopes->findAll();
    }
}
