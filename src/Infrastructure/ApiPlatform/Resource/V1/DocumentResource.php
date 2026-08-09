<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource\V1;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Entity\Document;
use App\Infrastructure\ApiPlatform\State\V1\DocumentProcessor;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Document',
    operations: [
        new Post(
            uriTemplate: '/documents',
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Operation(
                tags: ['V1 - Documents'],
                summary: 'Store a document',
                description: 'Ingests a document, checks it against the active regulatory scopes (Native Compliance Core), encrypts it (envelope encryption) and stores it on the Zero Trust storage backend.',
            ),
            deserialize: false,
            processor: DocumentProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class DocumentResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    /**
     * With `deserialize: false`, API Platform never populates these fields
     * itself: DocumentProcessor reads them from the raw request and
     * validates them explicitly against these constraints before use.
     */
    #[Assert\NotNull(message: 'A valid "file" upload is required.')]
    #[Assert\File]
    public ?UploadedFile $file = null;

    #[Assert\NotBlank]
    public ?string $documentType = null;

    #[Assert\NotBlank]
    public ?string $ownerId = null;

    #[Assert\NotBlank]
    public ?string $country = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $retentionYears = null;

    public ?string $fileHash = null;

    public ?string $storageKey = null;

    public ?string $createdAt = null;

    public static function fromEntity(Document $document): self
    {
        $resource = new self();
        $resource->id = $document->id()->value;
        $resource->documentType = $document->documentType()->value;
        $resource->ownerId = $document->ownerId()->value;
        $resource->country = $document->metadata()->country;
        $resource->retentionYears = $document->metadata()->retentionYears;
        $resource->fileHash = $document->fileHash()->value;
        $resource->storageKey = $document->storageKey()->value;
        $resource->createdAt = $document->createdAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
