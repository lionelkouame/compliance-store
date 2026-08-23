<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetRegulatoryScopeByCode\GetRegulatoryScopeByCodeUseCase;
use App\Application\UseCase\GetRegulatoryScopeById\GetRegulatoryScopeByIdUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\RegulatoryScopeResource;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<RegulatoryScopeResource>
 */
final readonly class RegulatoryScopeItemProvider implements ProviderInterface
{
    public function __construct(
        private GetRegulatoryScopeByIdUseCase $getByIdUseCase,
        private GetRegulatoryScopeByCodeUseCase $getByCodeUseCase,
    ) {}

    /**
     * @param array{id?: string, code?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?RegulatoryScopeResource
    {
        $idOrCode = $uriVariables['id'] ?? $uriVariables['code'] ?? '';

        if (Uuid::isValid($idOrCode)) {
            $scope = $this->getByIdUseCase->execute($idOrCode);
        } else {
            $scope = $this->getByCodeUseCase->execute($idOrCode);
        }

        if (null === $scope) {
            return null;
        }

        return RegulatoryScopeResource::fromEntity($scope);
    }
}
