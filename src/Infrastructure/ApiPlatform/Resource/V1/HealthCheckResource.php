<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource\V1;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Infrastructure\ApiPlatform\State\V1\HealthCheckStateProvider;
use ApiPlatform\OpenApi\Model\Operation;

#[ApiResource(
    shortName: 'HealthCheck',
    operations: [
        new Get(
            uriTemplate: '/health',
            openapi: new Operation(
                tags: ['V1 - System'],
                summary: 'Check API health status (V1)',
                description: 'Lightweight test endpoint to validate Clean Architecture V1.'
            ),
            description: 'Check V1 API health status and Clean Architecture',
            provider: HealthCheckStateProvider::class
        ),
    ],
    routePrefix: '/v1'
)]
final class HealthCheckResource
{
    public string $status;
    public string $version;
    public string $environment;
    public string $timestamp;
}

