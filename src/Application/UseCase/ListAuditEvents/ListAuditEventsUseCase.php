<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListAuditEvents;

use App\Domain\Entity\AuditEvent;
use App\Domain\Port\Repository\AuditEventRepositoryInterface;

final readonly class ListAuditEventsUseCase
{
    public const int DEFAULT_LIMIT = 100;
    public const int MAX_LIMIT = 1000;

    public function __construct(
        private AuditEventRepositoryInterface $events,
    ) {}

    /**
     * @param ?string $subjectId restrict to one holder or document
     *
     * @return list<AuditEvent> newest first
     */
    public function execute(?string $subjectId = null, int $limit = self::DEFAULT_LIMIT): array
    {
        $subjectId = null === $subjectId || '' === trim($subjectId) ? null : trim($subjectId);
        $limit = max(1, min($limit, self::MAX_LIMIT));

        return $this->events->findLatest($subjectId, $limit);
    }
}
