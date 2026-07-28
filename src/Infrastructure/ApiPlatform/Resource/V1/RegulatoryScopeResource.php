<?php

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
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'RegulatoryScope',
    operations: [
        new GetCollection(
            uriTemplate: '/v1/regulatory-scopes',
            openapi: new Operation(
                tags: ['V1 - Regulatory Scopes'],
                summary: 'Liste les périmètres réglementaires',
                description: 'Liste tous les périmètres réglementaires (RegulatoryScope) administrés dynamiquement.',
            ),
            provider: RegulatoryScopeCollectionProvider::class,
        ),
        new Get(
            uriTemplate: '/v1/regulatory-scopes/{code}',
            uriVariables: ['code'],
            openapi: new Operation(
                tags: ['V1 - Regulatory Scopes'],
                summary: 'Détail d\'un périmètre réglementaire',
                description: 'Récupère un périmètre réglementaire par son code unique.',
            ),
            provider: RegulatoryScopeItemProvider::class,
        ),
        new Post(
            uriTemplate: '/v1/regulatory-scopes',
            openapi: new Operation(
                tags: ['V1 - Regulatory Scopes'],
                summary: 'Crée un périmètre réglementaire',
                description: 'Crée dynamiquement un nouveau périmètre réglementaire, immédiatement utilisable par le moteur de règles.',
            ),
            processor: RegulatoryScopeProcessor::class,
        ),
    ],
)]
final class RegulatoryScopeResource
{
    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Z][A-Z0-9_]*$/',
        message: 'Le code doit être en MAJUSCULES_SNAKE_CASE (ex: KYC_INDIVIDUAL).',
    )]
    public ?string $code = null;

    #[Assert\NotBlank]
    public ?string $label = null;

    public string $description = '';

    /**
     * @var list<string>
     */
    public array $allowedDocumentTypes = [];

    public bool $isActive = true;

    public ?string $createdAt = null;

    public ?string $updatedAt = null;

    public static function fromEntity(RegulatoryScope $scope): self
    {
        $resource = new self();
        $resource->code = $scope->code()->value;
        $resource->label = $scope->label()->value;
        $resource->description = $scope->description()->value;
        $resource->allowedDocumentTypes = $scope->allowedDocumentTypes()->toArray();
        $resource->isActive = $scope->isActive();
        $resource->createdAt = $scope->createdAt()->format(\DateTimeInterface::ATOM);
        $resource->updatedAt = $scope->updatedAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
