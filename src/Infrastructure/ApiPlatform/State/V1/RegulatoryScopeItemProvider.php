<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetRegulatoryScopeByCode\GetRegulatoryScopeByCodeUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\RegulatoryScopeResource;

/**
 * @implements ProviderInterface<RegulatoryScopeResource>
 */
final readonly class RegulatoryScopeItemProvider implements ProviderInterface
{
    public function __construct(
        private GetRegulatoryScopeByCodeUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?RegulatoryScopeResource
    {
        $scope = $this->useCase->execute((string) ($uriVariables['code'] ?? ''));

        return null !== $scope ? RegulatoryScopeResource::fromEntity($scope) : null;
    }
}
