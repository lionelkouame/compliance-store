<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Domain\Entity\Document;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\DocumentProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\GetDocumentContentProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\GetDocumentProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\ListHolderDocumentsProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Document',
    operations: [
        new Post(
            uriTemplate: '/documents',
            security: "is_granted('ROLE_OPERATOR')",
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Operation(
                tags: ['V1 - Documents'],
                summary: 'Store a document',
                description: 'Ingests a document on behalf of a holder, encrypts it (envelope encryption) and stores it on the Zero Trust storage backend.',
            ),
            deserialize: false,
            processor: DocumentProcessor::class,
        ),
        new Get(
            uriTemplate: '/documents/{id}',
            security: "is_granted('ROLE_READER')",
            openapi: new Operation(
                tags: ['V1 - Documents'],
                summary: 'Get a document',
                description: 'Metadata of a stored document: holder, content hash, storage date. The content itself is served by /documents/{id}/content.',
            ),
            provider: GetDocumentProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/holders/{holderId}/documents',
            uriVariables: ['holderId' => new Link(fromClass: HolderResource::class, identifiers: ['id'])],
            security: "is_granted('ROLE_READER')",
            paginationEnabled: false,
            openapi: new Operation(
                tags: ['V1 - Documents'],
                summary: 'List the documents of a holder',
                description: 'Every document stored for the holder, oldest first. 404 for an unknown holder.',
            ),
            provider: ListHolderDocumentsProvider::class,
        ),
        new Get(
            uriTemplate: '/documents/{id}/content',
            // Decrypted personal data: reading it is an operator action, and every read is audited.
            security: "is_granted('ROLE_OPERATOR')",
            outputFormats: ['binary' => ['application/octet-stream']],
            openapi: new Operation(
                tags: ['V1 - Documents'],
                summary: 'Download the decrypted content of a document',
                description: 'Decrypts the document and checks it against the hash recorded at store time before releasing it. Send "Accept: application/octet-stream" (any other representation gives 406). Each download is written to the audit trail.',
                responses: [
                    '200' => new OpenApiResponse(
                        description: 'The decrypted bytes. X-Document-Hash carries the SHA-256 of the content.',
                        content: new \ArrayObject(['application/octet-stream' => ['schema' => ['type' => 'string', 'format' => 'binary']]]),
                    ),
                    '404' => new OpenApiResponse(description: 'Unknown document'),
                    '406' => new OpenApiResponse(description: 'Accept header does not allow application/octet-stream'),
                    '409' => new OpenApiResponse(description: 'The stored content does not match its recorded hash'),
                    '502' => new OpenApiResponse(description: 'Storage or decryption failure'),
                ],
            ),
            provider: GetDocumentContentProvider::class,
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
     * The size and MIME type limits (DOCUMENT_MAX_SIZE, DOCUMENT_MIME_TYPES)
     * are deployment settings, checked by the processor at runtime.
     */
    #[Assert\NotNull(message: 'A valid "file" upload is required.')]
    #[Assert\File]
    // Input only: never serialised back in the 201 response nor in the output schema.
    #[ApiProperty(readable: false)]
    public ?UploadedFile $file = null;

    /**
     * Identifier of the Holder (data subject) the document belongs to,
     * sent as a regular multipart field next to the file.
     */
    #[Assert\NotBlank(message: 'A "holderId" is required.')]
    #[Assert\Uuid(message: 'The "holderId" must be a valid UUID.')]
    public ?string $holderId = null;

    public ?string $fileHash = null;

    public ?string $createdAt = null;

    public static function fromEntity(Document $document): self
    {
        $resource = new self();
        $resource->id = $document->id()->value;
        $resource->holderId = $document->holderId()->value;
        $resource->fileHash = $document->fileHash()->value;
        $resource->createdAt = $document->createdAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
