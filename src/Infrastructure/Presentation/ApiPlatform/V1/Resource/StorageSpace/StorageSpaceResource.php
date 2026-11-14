<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\StorageSpace\ChangeStorageSpaceStatusProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\StorageSpace\CreateStorageSpaceProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\StorageSpace\GetStorageSpaceProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\StorageSpace\ListStorageSpacesProvider;

#[ApiResource(
    shortName: 'StorageSpace',
    operations: [
        new Post(
            uriTemplate: '/storage-spaces',
            openapi: new Operation(
                tags: self::TagStorageSpaceSection,
                summary: 'Create a storage space',
                description: 'Registers a new storage space, active by default.',
            ),
            input: CreateStorageSpaceInput::class,
            output: StorageSpaceOutput::class,
            deserialize: true,
            processor: CreateStorageSpaceProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/storage-spaces',
            openapi: new Operation(
                tags: self::TagStorageSpaceSection,
                summary: 'List storage spaces',
                description: 'Lists every registered storage space.',
            ),
            output: StorageSpaceOutput::class,
            provider: ListStorageSpacesProvider::class,
        ),
        new Get(
            uriTemplate: '/storage-spaces/{id}',
            openapi: new Operation(
                tags: self::TagStorageSpaceSection,
                summary: 'Get a storage space',
                description: 'Retrieves a single storage space by id.',
            ),
            output: StorageSpaceOutput::class,
            provider: GetStorageSpaceProvider::class,
        ),
        new Patch(
            uriTemplate: '/storage-spaces/{id}',
            openapi: new Operation(
                tags: self::TagStorageSpaceSection,
                summary: 'Change a storage space status',
                description: 'Transitions a storage space to active, inactive or archived.',
            ),
            input: ChangeStorageSpaceStatusInput::class,
            output: StorageSpaceOutput::class,
            read: false,
            deserialize: true,
            processor: ChangeStorageSpaceStatusProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class StorageSpaceResource
{
    public const array TagStorageSpaceSection = ['V1 - Storage Spaces'];
}
