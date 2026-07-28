<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateRegulatoryScope;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Exception\RegulatoryScopeAlreadyExistsException;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\Port\Service\IdGeneratorInterface;
use App\Domain\ValueObject\AllowedDocumentTypes;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Domain\ValueObject\RegulatoryScopeDescription;
use App\Domain\ValueObject\RegulatoryScopeId;
use App\Domain\ValueObject\RegulatoryScopeLabel;

final readonly class CreateRegulatoryScopeUseCase
{
    public function __construct(
        private RegulatoryScopeRepositoryInterface $regulatoryScopes,
        private IdGeneratorInterface $idGenerator,
    ) {}

    /**
     * @throws RegulatoryScopeAlreadyExistsException if the code already exists
     */
    public function execute(CreateRegulatoryScopeCommand $command): RegulatoryScope
    {
        $code = new RegulatoryScopeCode($command->code);

        if ($this->regulatoryScopes->existsByCode($code)) {
            throw RegulatoryScopeAlreadyExistsException::forCode($command->code);
        }

        $id = null !== $command->id ? RegulatoryScopeId::fromString($command->id) : $this->idGenerator->generate();

        $scope = RegulatoryScope::create(
            id: $id,
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
