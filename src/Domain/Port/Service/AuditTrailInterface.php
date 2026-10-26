<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\AuditAction;

/**
 * Appends to the audit trail. The adapter knows who the current actor is
 * (authenticated API client, console command...) and generates the event id:
 * use cases only say what happened to which subject.
 */
interface AuditTrailInterface
{
    public function record(AuditAction $action, string $subjectId): void;
}
