<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetRegulatoryScopeById;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\RegulatoryScopeId;

final readonly class GetRegulatoryScopeByIdUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
        private IdValidatorInterface $idValidator,
    ) {}

    public function execute(string $id): ?RegulatoryScope
    {
        if (!$this->idValidator->isValid($id)) {
            return null;
        }

        return $this->regulatoryScopes->findById(RegulatoryScopeId::fromString($id, $this->idValidator));
    }
}
