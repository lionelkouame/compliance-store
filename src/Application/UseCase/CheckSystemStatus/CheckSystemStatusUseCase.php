<?php

declare(strict_types=1);

namespace App\Application\UseCase\CheckSystemStatus;

use App\Domain\ValueObject\SystemStatus;

final readonly class CheckSystemStatusUseCase
{
    public const string STATUS_OK = 'OK';

    /**
     * Both values are bound in config/services.yaml (kernel environment and API version).
     */
    public function __construct(
        private string $environment,
        private string $version,
    ) {}

    public function execute(): SystemStatus
    {
        return new SystemStatus(
            status: self::STATUS_OK,
            version: $this->version,
            environment: $this->environment,
            timestamp: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
