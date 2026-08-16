<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource\V1;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Entity\RegulatoryScope;
use App\Infrastructure\ApiPlatform\State\V1\RegulatoryScopeCollectionProvider;
use App\Infrastructure\ApiPlatform\State\V1\RegulatoryScopeItemProvider;
use App\Infrastructure\ApiPlatform\State\V1\RegulatoryScopeProcessor;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertRegulatoryScopeCodeUnique;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'RegulatoryScope',
    operations: [
        new GetCollection(
            uriTemplate: '/regulatory-scopes',
            openapi: new Operation(
                tags: ['V1 - Regulatory Scopes'],
                summary: 'List regulatory scopes',
                description: 'List all dynamically managed regulatory scopes (RegulatoryScope).',
            ),
            provider: RegulatoryScopeCollectionProvider::class,
        ),
        new Get(
            uriTemplate: '/regulatory-scopes/{id}',
            uriVariables: ['id'],
            openapi: new Operation(
                tags: ['V1 - Regulatory Scopes'],
                summary: 'Get regulatory scope details',
                description: 'Retrieve a regulatory scope by its UUID or unique code.',
            ),
            provider: RegulatoryScopeItemProvider::class,
        ),
        new Post(
            uriTemplate: '/regulatory-scopes',
            openapi: new Operation(
                tags: ['V1 - Regulatory Scopes'],
                summary: 'Create a regulatory scope',
                description: 'Dynamically create a new regulatory scope, immediately available for the rules engine.',
            ),
            processor: RegulatoryScopeProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class RegulatoryScopeResource
{
    #[ApiProperty(identifier: true, writable: false)]
    #[Assert\Uuid]
    public ?string $id = null;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Z][A-Z0-9_]*$/D',
        message: 'The code must be in UPPERCASE_SNAKE_CASE (e.g. KYC_INDIVIDUAL).',
    )]
    #[AssertRegulatoryScopeCodeUnique]
    public ?string $code = null;

    #[Assert\NotBlank]
    public ?string $label = null;

    public string $description = '';

    /**
     * @var list<string>
     */
    public array $allowedDocumentTypes = [];

    public bool $isActive = true;

    #[ApiProperty(writable: false)]
    public ?string $createdAt = null;

    #[ApiProperty(writable: false)]
    public ?string $updatedAt = null;

    public static function fromEntity(RegulatoryScope $scope): self
    {
        $allowedDocumentTypes = [];
        foreach ($scope->allowedDocumentTypes() as $documentType) {
            $allowedDocumentTypes[] = $documentType->value;
        }

        $resource = new self();
        $resource->id = $scope->id()->value;
        $resource->code = $scope->code()->value;
        $resource->label = $scope->label()->value;
        $resource->description = $scope->description()->value;
        $resource->allowedDocumentTypes = $allowedDocumentTypes;
        $resource->isActive = $scope->isActive();
        $resource->createdAt = $scope->createdAt()->format(\DateTimeInterface::ATOM);
        $resource->updatedAt = $scope->updatedAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
