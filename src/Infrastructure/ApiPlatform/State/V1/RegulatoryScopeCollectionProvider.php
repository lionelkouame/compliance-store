<?php

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListRegulatoryScopes\ListRegulatoryScopesUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\RegulatoryScopeResource;

/**
 * @implements ProviderInterface<RegulatoryScopeResource>
 */
final readonly class RegulatoryScopeCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListRegulatoryScopesUseCase $useCase,
    ) {}

    /**
     * @return list<RegulatoryScopeResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(
            RegulatoryScopeResource::fromEntity(...),
            $this->useCase->execute(),
        );
    }
}
