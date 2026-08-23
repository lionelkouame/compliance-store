<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListRegulatoryScopes\ListRegulatoryScopesUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\RegulatoryScopeResource;

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
