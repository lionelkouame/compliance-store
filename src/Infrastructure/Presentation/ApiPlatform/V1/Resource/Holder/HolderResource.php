<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder\CreateHolderProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\Holder\GetHolderProvider;

#[ApiResource(
    shortName: 'Holder',
    operations: [
        new Post(
            uriTemplate: '/holders',
            openapi: new Operation(
                tags: self::TagHolderSection,
                summary: 'Create a holder',
                description: 'Create a holder, active by default.',
            ),
            input: CreateHolderInput::class,
            output: CreateHolderOutput::class,
            deserialize: true,
            processor: CreateHolderProcessor::class
        ),
        new Get(
            uriTemplate: '/holders/{email}',
            openapi: new Operation(
                tags: self::TagHolderSection,
                summary: 'Get one Holder',
                description: 'Retrieves a single holder by email.',
            ),
            output: GetHolderEmailOutput::class,
            provider: GetHolderProvider::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class HolderResource
{
    public const array TagHolderSection = ['V1 - Holders'];
}
