<?php

namespace App\Application\UseCase\CheckSystemStatus;

use App\Domain\ValueObject\SystemStatus;

final class CheckSystemStatusUseCase
{
    public function __construct(
        private string $environment = 'dev',
        private string $version = '1.0.0',
    ) {}

    public function execute(): SystemStatus
    {
        return new SystemStatus(
            status: 'ok',
            version: $this->version,
            environment: $this->environment,
            timestamp: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
