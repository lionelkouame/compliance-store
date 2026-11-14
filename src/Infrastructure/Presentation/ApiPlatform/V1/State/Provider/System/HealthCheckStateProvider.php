<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\System;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\System\CheckSystemStatus\CheckSystemStatusRequest;
use App\Application\UseCase\System\CheckSystemStatus\CheckSystemStatusUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\System\HealthCheckOutput;

/**
 * @implements ProviderInterface<HealthCheckOutput>
 */
final readonly class HealthCheckStateProvider implements ProviderInterface
{
    public function __construct(
        private CheckSystemStatusUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HealthCheckOutput
    {
        $readModel = $this->useCase->execute(new CheckSystemStatusRequest())->readModel;

        return new HealthCheckOutput(
            status: $readModel->status,
            version: $readModel->version,
            environment: $readModel->environment,
            timestamp: $readModel->timestamp->format(\DateTimeInterface::ATOM),
        );
    }
}
