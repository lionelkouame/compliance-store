<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\System;

final readonly class HealthCheckOutput
{
    public function __construct(
        public string $status,
        public string $version,
        public string $environment,
        public string $timestamp,
    ) {}
}
