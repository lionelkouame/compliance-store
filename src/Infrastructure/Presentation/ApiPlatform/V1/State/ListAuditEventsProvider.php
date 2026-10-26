<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListAuditEvents\ListAuditEventsUseCase;
use App\Domain\Entity\AuditEvent;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\AuditEventResource;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<AuditEventResource>
 */
final readonly class ListAuditEventsProvider implements ProviderInterface
{
    public function __construct(
        private ListAuditEventsUseCase $useCase,
        private RequestStack $requestStack,
    ) {}

    /**
     * @return list<AuditEventResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        $subjectId = $query?->get('subjectId');
        $limit = $query?->get('limit');

        return array_map(
            static fn (AuditEvent $event): AuditEventResource => AuditEventResource::fromEntity($event),
            $this->useCase->execute(
                subjectId: \is_string($subjectId) ? $subjectId : null,
                limit: is_numeric($limit) ? (int) $limit : ListAuditEventsUseCase::DEFAULT_LIMIT,
            ),
        );
    }
}
