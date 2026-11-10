<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\CreateNaturalPersonProcessor;

#[ApiResource(
    shortName: 'NaturalPerson',
    operations: [
        new Post(
            uriTemplate: '/natural-persons',
            openapi: new Operation(
                tags: self::TagNaturalPersonSection,
                summary: 'Create a NaturalPerson',
                description: 'Create a NaturalPerson, active by default.',
            ),
            input: CreateNaturalPersonInput::class,
            output: CreateNaturalPersonOutput::class,
            deserialize: true,
            processor: CreateNaturalPersonProcessor::class
        ),
    ],
    routePrefix: '/v1',
)]
final class NaturalPersonResource
{
    public const array TagNaturalPersonSection = ['V1 - Natural Persons'];
}
