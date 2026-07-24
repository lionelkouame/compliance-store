<?php

namespace App\Infrastructure\ApiPlatform\Resource\V1;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Infrastructure\ApiPlatform\State\V1\HealthCheckStateProvider;

#[ApiResource(
    shortName: 'HealthCheck',
    operations: [
        new Get(
            uriTemplate: '/v1/health',
            description: 'Vérifie l\'état de santé de l\'API V1 et de la Clean Architecture',
            provider: HealthCheckStateProvider::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['V1 - System'],
                summary: 'Vérifie l\'état de santé de l\'API (V1)',
                description: 'Endpoint léger de test de validation de la Clean Architecture V1.'
            )
        ),
    ]
)]
final class HealthCheckResource
{
    public string $status;
    public string $version;
    public string $environment;
    public string $timestamp;
}
