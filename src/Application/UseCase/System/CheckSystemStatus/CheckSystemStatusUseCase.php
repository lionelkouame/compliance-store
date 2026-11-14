<?php

declare(strict_types=1);

namespace App\Application\UseCase\System\CheckSystemStatus;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\ReadModel\System\SystemStatus;

/**
 * @implements UseCaseInterface<CheckSystemStatusRequest, CheckSystemStatusResponse>
 */
final readonly class CheckSystemStatusUseCase implements UseCaseInterface
{
    public const string STATUS_OK = 'OK';

    public function __construct(
        private string $environment = 'dev',
        private string $version = '1.0.0',
    ) {}

    public function execute(UseCaseRequestInterface $request): CheckSystemStatusResponse
    {
        return new CheckSystemStatusResponse(new SystemStatus(
            status: self::STATUS_OK,
            version: $this->version,
            environment: $this->environment,
            timestamp: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ));
    }
}
