<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetLegalFrameworkByCode\GetLegalFrameworkByCodeUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\LegalFrameworkResource;

/**
 * @implements ProviderInterface<LegalFrameworkResource>
 */
final readonly class LegalFrameworkItemProvider implements ProviderInterface
{
    public function __construct(
        private GetLegalFrameworkByCodeUseCase $useCase,
    ) {}

    /**
     * @param array{code?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?LegalFrameworkResource
    {
        $legalFramework = $this->useCase->execute($uriVariables['code'] ?? '');
        if (null === $legalFramework) {
            return null;
        }

        return LegalFrameworkResource::fromEntity($legalFramework);
    }
}
