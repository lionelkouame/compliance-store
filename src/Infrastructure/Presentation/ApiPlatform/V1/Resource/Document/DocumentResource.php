<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\StoreDocumentProcessor;

#[ApiResource(
    shortName: 'Document',
    operations: [
        new Post(
            uriTemplate: '/documents',
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Operation(
                tags: self::TagDocumentSection,
                summary: 'Store a document',
                description: 'Ingests a document, encrypts it (envelope encryption) and stores it on the Zero Trust storage backend.',
            ),
            input: StoreDocumentInput::class,
            output: StoreDocumentOutput::class,
            deserialize: false,
            processor: StoreDocumentProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class DocumentResource
{
    public const array TagDocumentSection = ['V1 - Documents'];
}
