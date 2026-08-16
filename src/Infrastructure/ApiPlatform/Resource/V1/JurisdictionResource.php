<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource\V1;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Entity\Jurisdiction;
use App\Infrastructure\ApiPlatform\State\V1\JurisdictionCollectionProvider;
use App\Infrastructure\ApiPlatform\State\V1\JurisdictionItemProvider;
use App\Infrastructure\ApiPlatform\State\V1\JurisdictionProcessor;
use App\Infrastructure\ApiPlatform\State\V1\JurisdictionStatusProcessor;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertJurisdictionCodeUnique;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Jurisdiction',
    operations: [
        new GetCollection(
            uriTemplate: '/jurisdictions',
            openapi: new Operation(
                tags: ['V1 - Jurisdictions'],
                summary: 'List jurisdictions',
                description: 'List all dynamically managed territorial jurisdictions, optionally filtered by region, country and active status.',
            ),
            provider: JurisdictionCollectionProvider::class,
        ),
        new Get(
            uriTemplate: '/jurisdictions/{code}',
            uriVariables: ['code'],
            openapi: new Operation(
                tags: ['V1 - Jurisdictions'],
                summary: 'Get jurisdiction details',
                description: 'Retrieve a jurisdiction by its unique code.',
            ),
            provider: JurisdictionItemProvider::class,
        ),
        new Post(
            uriTemplate: '/jurisdictions',
            openapi: new Operation(
                tags: ['V1 - Jurisdictions'],
                summary: 'Create a jurisdiction',
                description: 'Dynamically create a new territorial jurisdiction, immediately available for the rules engine.',
            ),
            validationContext: ['groups' => ['Default', 'jurisdiction:create']],
            processor: JurisdictionProcessor::class,
        ),
        new Patch(
            uriTemplate: '/jurisdictions/{code}',
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']],
            uriVariables: ['code'],
            openapi: new Operation(
                tags: ['V1 - Jurisdictions'],
                summary: 'Activate or deactivate a jurisdiction',
                description: 'Dynamically activate or deactivate a jurisdiction by its unique code.',
            ),
            provider: JurisdictionItemProvider::class,
            processor: JurisdictionStatusProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class JurisdictionResource
{
    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^JUR-[A-Z0-9_-]+$/D',
        message: 'The code must match the format JUR-XXX (e.g. JUR-EU-FRA).',
    )]
    #[AssertJurisdictionCodeUnique(groups: ['jurisdiction:create'])]
    public ?string $code = null;

    #[ApiProperty(writable: false)]
    public ?string $id = null;

    #[Assert\NotBlank]
    public ?string $label = null;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Z]{2,}$/D',
        message: 'The region must be uppercase letters only (e.g. EU, NA, GLOBAL).',
    )]
    public ?string $region = null;

    #[Assert\Regex(
        pattern: '/^[A-Z]{3}$/D',
        message: 'The country must be an ISO 3166-1 alpha-3 code (e.g. FRA, DEU, USA).',
    )]
    public ?string $country = null;

    #[Assert\NotBlank(allowNull: true)]
    public ?string $subRegion = null;

    /**
     * @var list<string>
     */
    #[Assert\All([
        new Assert\Regex(
            pattern: '/^FRAMEWORK-[A-Z0-9_-]+$/D',
            message: 'Each applicable framework must match the format FRAMEWORK-XXX (e.g. FRAMEWORK-GDPR).',
        ),
    ])]
    public array $applicableFrameworks = [];

    public bool $active = true;

    #[ApiProperty(writable: false)]
    public ?string $createdAt = null;

    #[ApiProperty(writable: false)]
    public ?string $updatedAt = null;

    public static function fromEntity(Jurisdiction $jurisdiction): self
    {
        $applicableFrameworks = [];
        foreach ($jurisdiction->applicableFrameworks() as $framework) {
            $applicableFrameworks[] = $framework->value;
        }

        $resource = new self();
        $resource->id = $jurisdiction->id()->value;
        $resource->code = $jurisdiction->code()->value;
        $resource->label = $jurisdiction->label()->value;
        $resource->region = $jurisdiction->region()->value;
        $resource->country = $jurisdiction->country()?->value;
        $resource->subRegion = $jurisdiction->subRegion()?->value;
        $resource->applicableFrameworks = $applicableFrameworks;
        $resource->active = $jurisdiction->isActive();
        $resource->createdAt = $jurisdiction->createdAt()->format(\DateTimeInterface::ATOM);
        $resource->updatedAt = $jurisdiction->updatedAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
