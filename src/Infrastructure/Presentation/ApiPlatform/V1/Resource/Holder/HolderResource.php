<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder\AnonymizeHolderProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder\CreateHolderProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder\RequestHolderErasureProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder\SearchHolderProcessor;
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
            uriTemplate: '/holders/{id}',
            openapi: new Operation(
                tags: self::TagHolderSection,
                summary: 'Get one holder',
                description: 'Retrieves a single holder by its identifier.',
            ),
            output: HolderOutput::class,
            provider: GetHolderProvider::class,
        ),
        // Recherche par email : l'email voyage dans le corps, jamais dans l'URL (constat M6).
        new Post(
            uriTemplate: '/holders/search',
            status: 200,
            openapi: new Operation(
                tags: self::TagHolderSection,
                summary: 'Search a holder by email',
                description: 'Finds the holder registered with the given email. The email is sent in the request body so that it never appears in URLs or access logs.',
            ),
            input: SearchHolderInput::class,
            output: HolderOutput::class,
            deserialize: true,
            processor: SearchHolderProcessor::class,
        ),
        // Droit à l'effacement (RGPD art. 17), en deux temps : la demande, puis son exécution.
        new Post(
            uriTemplate: '/holders/{id}/erasure-request',
            status: 200,
            openapi: new Operation(
                tags: self::TagHolderSection,
                summary: 'Request the erasure of a holder',
                description: 'Records a GDPR erasure request (art. 17). Only an active holder can be the subject of a request. The erasure itself is carried out by the anonymization operation.',
            ),
            input: false,
            output: HolderOutput::class,
            read: false,
            deserialize: false,
            processor: RequestHolderErasureProcessor::class,
        ),
        new Post(
            uriTemplate: '/holders/{id}/anonymization',
            status: 200,
            openapi: new Operation(
                tags: self::TagHolderSection,
                summary: 'Anonymize a holder',
                description: 'Carries out a pending erasure request: deletes every document of the holder (encrypted content and data key), then replaces its name, email and birth date with non-identifying values.',
            ),
            input: false,
            output: HolderOutput::class,
            read: false,
            deserialize: false,
            processor: AnonymizeHolderProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class HolderResource
{
    public const array TagHolderSection = ['V1 - Holders'];
}
