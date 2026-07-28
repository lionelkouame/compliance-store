<?php

namespace App\Application\UseCase\CreateRegulatoryScope;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\RegulatoryScopeAlreadyExistsException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\AllowedDocumentTypes;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeDescription;
use App\Domain\ValueObject\RegulatoryScopeLabel;

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
        $code = new RegulatoryScopeCode($command->code);

        if ($this->regulatoryScopes->existsByCode($code)) {
            throw RegulatoryScopeAlreadyExistsException::forCode($command->code);
        }

        $scope = RegulatoryScope::create(
            code: $code,
            label: new RegulatoryScopeLabel($command->label),
            description: new RegulatoryScopeDescription($command->description),
            allowedDocumentTypes: AllowedDocumentTypes::fromStrings(...$command->allowedDocumentTypes),
            isActive: $command->isActive,
        );

        $this->regulatoryScopes->add($scope);

        return $scope;
    }
}
