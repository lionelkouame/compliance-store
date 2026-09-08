<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Entity\StorageSpace;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\ChangeStorageSpaceStatusProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\CreateStorageSpaceProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\GetStorageSpaceProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\ListStorageSpacesProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'StorageSpace',
    operations: [
        new Post(
            uriTemplate: '/storage-spaces',
            openapi: new Operation(
                tags: ['V1 - Storage Spaces'],
                summary: 'Create a storage space',
                description: 'Registers a new storage space, active by default.',
            ),
            deserialize: false,
            processor: CreateStorageSpaceProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/storage-spaces',
            openapi: new Operation(
                tags: ['V1 - Storage Spaces'],
                summary: 'List storage spaces',
                description: 'Lists every registered storage space.',
            ),
            provider: ListStorageSpacesProvider::class,
        ),
        new Get(
            uriTemplate: '/storage-spaces/{id}',
            openapi: new Operation(
                tags: ['V1 - Storage Spaces'],
                summary: 'Get a storage space',
                description: 'Retrieves a single storage space by id.',
            ),
            provider: GetStorageSpaceProvider::class,
        ),
        new Patch(
            uriTemplate: '/storage-spaces/{id}',
            openapi: new Operation(
                tags: ['V1 - Storage Spaces'],
                summary: 'Change a storage space status',
                description: 'Transitions a storage space to active, inactive or archived.',
            ),
            deserialize: false,
            provider: GetStorageSpaceProvider::class,
            processor: ChangeStorageSpaceStatusProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class StorageSpaceResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    /**
     * With `deserialize: false`, API Platform never populates these fields
     * itself: the processors read them from the raw request and validate
     * them explicitly against these constraints before use.
     */
    #[Assert\NotBlank(message: 'A storage space code is required.')]
    public ?string $code = null;

    #[Assert\NotBlank(message: 'A storage space name is required.')]
    public ?string $name = null;

    public ?string $status = null;

    public ?string $createdAt = null;

    public static function fromEntity(StorageSpace $storageSpace): self
    {
        $resource = new self();
        $resource->id = $storageSpace->id->value;
        $resource->code = $storageSpace->code->value;
        $resource->name = $storageSpace->name->value;
        $resource->status = $storageSpace->status->value;
        $resource->createdAt = $storageSpace->createdAt->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
