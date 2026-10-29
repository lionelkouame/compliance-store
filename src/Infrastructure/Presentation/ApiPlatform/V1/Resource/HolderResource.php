<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\CreateHolderInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\CreateHolderOutput;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\CreateHolderProcessor;

/**
 * No operation is exposed yet: they will be declared here once the matching
 * providers and processors exist under the V1 State namespace.
 */
#[ApiResource(
    shortName: 'Holder',
    operations: [
        new Post(
            uriTemplate: '/holders',
            openapi: new Operation(
                tags: ['V1 - Holders'],
                summary: 'Create a holder',
                description: 'Create a holder, active by default.',
            ),
            input: CreateHolderInput::class,
            output: CreateHolderOutput::class,
            deserialize: true,
            processor: CreateHolderProcessor::class

        ),
    ],
    routePrefix: '/v1',
)]
final class HolderResource
{
}
