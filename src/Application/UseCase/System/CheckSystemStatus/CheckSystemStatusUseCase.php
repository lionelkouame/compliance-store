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

    /**
     * Both values are injected by the container (config/services.yaml): no
     * default here, so a missing configuration fails loudly instead of
     * reporting "dev" in production.
     */
    public function __construct(
        private string $environment,
        private string $version,
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
