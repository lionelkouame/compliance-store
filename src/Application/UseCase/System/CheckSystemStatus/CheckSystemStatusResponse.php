<?php

declare(strict_types=1);

namespace App\Application\UseCase\System\CheckSystemStatus;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\System\SystemStatus;

final readonly class CheckSystemStatusResponse implements UseCaseResponseInterface
{
    public function __construct(
        public SystemStatus $readModel,
    ) {}
}
