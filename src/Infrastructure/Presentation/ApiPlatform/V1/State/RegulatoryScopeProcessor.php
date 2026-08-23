<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\CreateRegulatoryScope\CreateRegulatoryScopeCommand;
use App\Application\UseCase\CreateRegulatoryScope\CreateRegulatoryScopeUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\RegulatoryScopeResource;

/**
 * @implements ProcessorInterface<RegulatoryScopeResource, RegulatoryScopeResource>
 */
final readonly class RegulatoryScopeProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateRegulatoryScopeUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RegulatoryScopeResource
    {
        $scope = $this->useCase->execute(new CreateRegulatoryScopeCommand(
            code: (string) $data->code,
            label: (string) $data->label,
            description: $data->description,
            allowedDocumentTypes: $data->allowedDocumentTypes,
            isActive: $data->isActive,
        ));

        return RegulatoryScopeResource::fromEntity($scope);
    }
}
