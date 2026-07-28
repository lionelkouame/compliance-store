<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetRegulatoryScopeById;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeId;

final readonly class GetRegulatoryScopeByIdUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    public function execute(string $id): ?RegulatoryScope
    {
        try {
            $scopeId = RegulatoryScopeId::fromString($id);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->regulatoryScopes->findById($scopeId);
    }
}
