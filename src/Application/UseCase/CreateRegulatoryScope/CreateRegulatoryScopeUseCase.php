<?php

namespace App\Application\UseCase\CreateRegulatoryScope;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\RegulatoryScopeAlreadyExistsException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;

final readonly class CreateRegulatoryScopeUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    /**
     * @throws RegulatoryScopeAlreadyExistsException si le code existe déjà
     */
    public function execute(CreateRegulatoryScopeCommand $command): RegulatoryScope
    {
        if ($this->regulatoryScopes->existsByCode($command->code)) {
            throw RegulatoryScopeAlreadyExistsException::forCode($command->code);
        }

        $scope = RegulatoryScope::create(
            code: $command->code,
            label: $command->label,
            description: $command->description,
            allowedDocumentTypes: $command->allowedDocumentTypes,
            isActive: $command->isActive,
        );

        $this->regulatoryScopes->add($scope);

        return $scope;
    }
}
