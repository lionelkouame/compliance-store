<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Entity\Holder;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\AnonymizeHolderProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\GetHolderProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\HolderProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\RequestHolderErasureProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Holder',
    operations: [
        new Post(
            uriTemplate: '/holders',
            security: "is_granted('ROLE_OPERATOR')",
            openapi: new Operation(
                tags: ['V1 - Holders'],
                summary: 'Register a holder',
                description: 'Registers a holder (data subject), active by default.',
            ),
            deserialize: false,
            processor: HolderProcessor::class,
        ),
        new Get(
            uriTemplate: '/holders/{id}',
            security: "is_granted('ROLE_READER')",
            openapi: new Operation(
                tags: ['V1 - Holders'],
                summary: 'Get a holder',
                description: 'Retrieves a single holder by id.',
            ),
            provider: GetHolderProvider::class,
        ),
        new Post(
            uriTemplate: '/holders/{id}/erasure-request',
            // Restricted to administrators: it starts the GDPR erasure workflow.
            security: "is_granted('ROLE_ADMIN')",
            status: 200,
            openapi: new Operation(
                tags: ['V1 - Holders'],
                summary: 'Record an erasure request (GDPR art. 17)',
                description: 'Flags an active holder as having asked for erasure. Nothing is deleted yet. 409 when the holder is not active.',
                requestBody: null,
            ),
            deserialize: false,
            processor: RequestHolderErasureProcessor::class,
        ),
        new Post(
            uriTemplate: '/holders/{id}/anonymization',
            security: "is_granted('ROLE_ADMIN')",
            status: 200,
            openapi: new Operation(
                tags: ['V1 - Holders'],
                summary: 'Carry out a pending erasure request',
                description: 'Destroys every document of the holder (ciphertext and wrapped key) and anonymizes the record: name replaced, birth date and email dropped. 409 when no erasure request is pending.',
                requestBody: null,
            ),
            deserialize: false,
            processor: AnonymizeHolderProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class HolderResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    /**
     * With `deserialize: false`, API Platform never populates these fields
     * itself: HolderProcessor reads them from the raw request and validates
     * them explicitly against these constraints before use.
     */
    #[Assert\NotBlank(message: 'A holder name is required.')]
    public ?string $holderName = null;

    /**
     * Required on registration; null in the output once the holder is anonymized.
     */
    #[Assert\NotBlank(message: 'A birth date is required.')]
    #[Assert\Date(message: 'The birth date must use the Y-m-d format.')]
    public ?string $birthDate = null;

    /**
     * Required on registration; null in the output once the holder is anonymized.
     */
    #[Assert\NotBlank(message: 'An email is required.')]
    #[Assert\Email(message: 'The email must be a valid address.')]
    public ?string $email = null;

    public ?string $status = null;

    public ?string $createdAt = null;

    public ?string $consentCollectedAt = null;

    public ?string $erasureRequestedAt = null;

    public ?string $anonymizedAt = null;

    public static function fromEntity(Holder $holder): self
    {
        $resource = new self();
        $resource->id = $holder->id()->value;
        $resource->holderName = $holder->name()->value;
        $resource->birthDate = null === $holder->birthDate() ? null : (string) $holder->birthDate();
        $resource->email = $holder->email()?->value;
        $resource->status = $holder->status()->value;
        $resource->createdAt = $holder->createdAt()->format(\DateTimeInterface::ATOM);
        $resource->consentCollectedAt = $holder->consentCollectedAt()?->format(\DateTimeInterface::ATOM);
        $resource->erasureRequestedAt = $holder->erasureRequestedAt()?->format(\DateTimeInterface::ATOM);
        $resource->anonymizedAt = $holder->anonymizedAt()?->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
