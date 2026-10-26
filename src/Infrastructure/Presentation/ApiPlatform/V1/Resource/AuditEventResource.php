<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Application\UseCase\ListAuditEvents\ListAuditEventsUseCase;
use App\Domain\Entity\AuditEvent;
use App\Infrastructure\Presentation\ApiPlatform\V1\State\ListAuditEventsProvider;

#[ApiResource(
    shortName: 'AuditEvent',
    operations: [
        new GetCollection(
            uriTemplate: '/audit-events',
            security: "is_granted('ROLE_ADMIN')",
            paginationEnabled: false,
            openapi: new Operation(
                tags: ['V1 - Audit'],
                summary: 'List audit events',
                description: 'Who did what to which holder or document, newest first. Read-only: the trail is append-only.',
                parameters: [
                    new Parameter(name: 'subjectId', in: 'query', description: 'Restrict to one holder or document id.', schema: ['type' => 'string', 'format' => 'uuid']),
                    new Parameter(name: 'limit', in: 'query', description: 'Maximum number of events (default 100, max 1000).', schema: ['type' => 'integer', 'minimum' => 1, 'maximum' => ListAuditEventsUseCase::MAX_LIMIT]),
                ],
            ),
            provider: ListAuditEventsProvider::class,
        ),
    ],
    routePrefix: '/v1',
)]
final class AuditEventResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    public ?string $action = null;

    public ?string $subjectId = null;

    public ?string $actor = null;

    public ?string $occurredAt = null;

    public static function fromEntity(AuditEvent $event): self
    {
        $resource = new self();
        $resource->id = $event->id();
        $resource->action = $event->action()->value;
        $resource->subjectId = $event->subjectId();
        $resource->actor = $event->actor();
        $resource->occurredAt = $event->occurredAt()->format(\DateTimeInterface::ATOM);

        return $resource;
    }
}
