<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetJurisdictionByCode\GetJurisdictionByCodeUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\JurisdictionResource;

/**
 * @implements ProviderInterface<JurisdictionResource>
 */
final readonly class JurisdictionItemProvider implements ProviderInterface
{
    public function __construct(
        private GetJurisdictionByCodeUseCase $useCase,
    ) {}

    /**
     * @param array{code?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?JurisdictionResource
    {
        $jurisdiction = $this->useCase->execute($uriVariables['code'] ?? '');
        if (null === $jurisdiction) {
            return null;
        }

        return JurisdictionResource::fromEntity($jurisdiction);
    }
}
