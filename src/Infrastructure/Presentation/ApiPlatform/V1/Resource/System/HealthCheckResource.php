<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\System;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\System\HealthCheckStateProvider;

#[ApiResource(
    shortName: 'HealthCheck',
    operations: [
        new Get(
            uriTemplate: '/health',
            openapi: new Operation(
                tags: self::TagSystemSection,
                summary: 'Check API health status (V1)',
                description: 'Lightweight test endpoint to validate Clean Architecture V1.'
            ),
            description: 'Check V1 API health status and Clean Architecture',
            output: HealthCheckOutput::class,
            provider: HealthCheckStateProvider::class,
        ),
    ],
    routePrefix: '/v1'
)]
final class HealthCheckResource
{
    public const array TagSystemSection = ['V1 - System'];
}
