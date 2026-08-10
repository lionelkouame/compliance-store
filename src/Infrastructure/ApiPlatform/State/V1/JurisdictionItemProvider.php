<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetJurisdictionByCode\GetJurisdictionByCodeUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\JurisdictionResource;

/**
 * @implements ProviderInterface<JurisdictionResource>
 */
final readonly class JurisdictionItemProvider implements ProviderInterface
{
    public function __construct(
        private GetJurisdictionByCodeUseCase $useCase,
    ) {}

    /**
     * @param array{code: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?JurisdictionResource
    {
        $jurisdiction = $this->useCase->execute($uriVariables['code']);

        return null !== $jurisdiction ? JurisdictionResource::fromEntity($jurisdiction) : null;
    }
}
