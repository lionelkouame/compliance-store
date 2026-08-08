<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetRegulatoryScopeByCode\GetRegulatoryScopeByCodeUseCase;
use App\Application\UseCase\GetRegulatoryScopeById\GetRegulatoryScopeByIdUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\RegulatoryScopeResource;
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

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?RegulatoryScopeResource
    {
        $rawIdOrCode = $uriVariables['id'] ?? $uriVariables['code'] ?? '';
        $idOrCode = \is_string($rawIdOrCode) ? $rawIdOrCode : '';

        if (Uuid::isValid($idOrCode)) {
            $scope = $this->getByIdUseCase->execute($idOrCode);
        } else {
            $scope = $this->getByCodeUseCase->execute($idOrCode);
        }

        return null !== $scope ? RegulatoryScopeResource::fromEntity($scope) : null;
    }
}
