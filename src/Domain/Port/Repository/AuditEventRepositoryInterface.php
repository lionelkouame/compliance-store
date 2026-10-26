<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\AuditEvent;

interface AuditEventRepositoryInterface
{
    public function add(AuditEvent $event): void;

    /**
     * @param ?string $subjectId restrict to one holder or document
     * @param int     $limit     maximum number of events, newest first
     *
     * @return list<AuditEvent>
     */
    public function findLatest(?string $subjectId, int $limit): array;
}
