<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\IdentityDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\IdentityDocument\RegisterIdentityDocumentProcessor;

#[ApiResource(
    shortName: 'IdentityDocument',
    operations: [
        new Post(
            uriTemplate: '/identity-documents',
            openapi: new Operation(
                tags: self::TagIdentityDocumentSection,
                summary: 'Register an identity document',
                description: 'Registers a passport or national ID card that has not expired yet.',
            ),
            input: RegisterIdentityDocumentInput::class,
            output: RegisterIdentityDocumentOutput::class,
            deserialize: true,
            collectDenormalizationErrors: true,
            processor: RegisterIdentityDocumentProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class IdentityDocumentResource
{
    public const array TagIdentityDocumentSection = ['V1 - Identity Documents'];
}
