<?php

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\CheckSystemStatus\CheckSystemStatusUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\HealthCheckResource;

/**
 * @implements ProviderInterface<HealthCheckResource>
 */
final readonly class HealthCheckStateProvider implements ProviderInterface
{
    public function __construct(
        private CheckSystemStatusUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HealthCheckResource
    {
        $status = $this->useCase->execute();

        $resource = new HealthCheckResource();
        $resource->status = $status->status;
        $resource->version = $status->version;
        $resource->environment = $status->environment;
        $resource->timestamp = $status->timestamp->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
