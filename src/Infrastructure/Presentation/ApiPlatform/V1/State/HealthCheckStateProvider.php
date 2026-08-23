<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\CheckSystemStatus\CheckSystemStatusUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\HealthCheckResource;

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
