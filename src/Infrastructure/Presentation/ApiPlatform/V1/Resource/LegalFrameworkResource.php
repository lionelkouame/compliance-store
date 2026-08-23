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
use App\Domain\Entity\LegalFramework;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\LegalFrameworkCollectionProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\LegalFrameworkItemProvider;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\LegalFrameworkProcessor;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\LegalFrameworkStatusProcessor;
use App\Infrastructure\Validation\Constraint\AssertLegalFrameworkCodeUnique;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'LegalFramework',
    operations: [
        new GetCollection(
            uriTemplate: '/legal-frameworks',
            openapi: new Operation(
                tags: ['V1 - Legal Frameworks'],
                summary: 'List legal frameworks',
                description: 'List all dynamically managed legal/regulatory frameworks, optionally filtered by jurisdiction and active status.',
            ),
            provider: LegalFrameworkCollectionProvider::class,
        ),
        new Get(
            uriTemplate: '/legal-frameworks/{code}',
            uriVariables: ['code'],
            openapi: new Operation(
                tags: ['V1 - Legal Frameworks'],
                summary: 'Get legal framework details',
                description: 'Retrieve a legal framework by its unique code.',
            ),
            provider: LegalFrameworkItemProvider::class,
        ),
        new Post(
            uriTemplate: '/legal-frameworks',
            openapi: new Operation(
                tags: ['V1 - Legal Frameworks'],
                summary: 'Create a legal framework',
                description: 'Dynamically register a new legal/regulatory framework, immediately available to justify storage and retention policies.',
            ),
            validationContext: ['groups' => ['Default', 'legal-framework:create']],
            processor: LegalFrameworkProcessor::class,
        ),
        new Patch(
            uriTemplate: '/legal-frameworks/{code}',
            uriVariables: ['code'],
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']],
            openapi: new Operation(
                tags: ['V1 - Legal Frameworks'],
                summary: 'Activate or deactivate a legal framework',
                description: 'Dynamically activate or deactivate a legal framework by its unique code.',
            ),
            provider: LegalFrameworkItemProvider::class,
            processor: LegalFrameworkStatusProcessor::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class LegalFrameworkResource
{
    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^FRAMEWORK-[A-Z0-9_-]+$/D',
        message: 'The code must match the format FRAMEWORK-XXX (e.g. FRAMEWORK-GDPR).',
    )]
    #[AssertLegalFrameworkCodeUnique(groups: ['legal-framework:create'])]
    public ?string $code = null;

    #[ApiProperty(writable: false)]
    public ?string $id = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\NotBlank]
    public ?string $officialReference = null;

    #[Assert\NotBlank]
    public ?string $regulatoryAuthority = null;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^JUR-[A-Z0-9_-]+$/D',
        message: 'The jurisdiction code must match the format JUR-XXX (e.g. JUR-EU-FRA).',
    )]
    public ?string $jurisdictionCode = null;

    public bool $active = true;

    #[ApiProperty(writable: false)]
    public ?string $createdAt = null;

    #[ApiProperty(writable: false)]
    public ?string $updatedAt = null;

    public static function fromEntity(LegalFramework $legalFramework): self
    {
        $resource = new self();
        $resource->id = $legalFramework->id()->value;
        $resource->code = $legalFramework->code()->value;
        $resource->name = $legalFramework->name()->value;
        $resource->officialReference = $legalFramework->officialReference()->value;
        $resource->regulatoryAuthority = $legalFramework->regulatoryAuthority()->value;
        $resource->jurisdictionCode = $legalFramework->jurisdictionCode()->value;
        $resource->active = $legalFramework->isActive();
        $resource->createdAt = $legalFramework->createdAt()->format(\DateTimeInterface::ATOM);
        $resource->updatedAt = $legalFramework->updatedAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
