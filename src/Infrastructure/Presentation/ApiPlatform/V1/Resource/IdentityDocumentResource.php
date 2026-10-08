<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Entity\IdentityDocument;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\RegisterIdentityDocumentProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'IdentityDocument',
    operations: [
        new Post(
            uriTemplate: '/identity-documents',
            openapi: new Operation(
                tags: ['V1 - Identity Documents'],
                summary: 'Register an identity document',
                description: 'Registers a passport or national ID card that has not expired yet.',
            ),
            deserialize: false,
            processor: RegisterIdentityDocumentProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class IdentityDocumentResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    /**
     * With `deserialize: false`, API Platform never populates these fields
     * itself: the processor reads them from the raw request and validates
     * them explicitly against these constraints before use. Format rules
     * (country code, number, date) are enforced by the Domain.
     */
    #[Assert\NotBlank(message: 'An identity document type is required.')]
    public ?string $type = null;

    #[Assert\NotBlank(message: 'An issuing country is required.')]
    public ?string $country = null;

    #[Assert\NotBlank(message: 'An identity document number is required.')]
    public ?string $number = null;

    #[Assert\NotBlank(message: 'An expiry date is required.')]
    public ?string $expiresAt = null;

    public ?string $createdAt = null;

    public static function fromEntity(IdentityDocument $identityDocument): self
    {
        $resource = new self();
        $resource->id = $identityDocument->id()->value;
        $resource->type = $identityDocument->type()->value;
        $resource->country = $identityDocument->country()->value;
        $resource->number = $identityDocument->number()->value;
        $resource->expiresAt = $identityDocument->expiresAt()->format('Y-m-d');
        $resource->createdAt = $identityDocument->createdAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
