<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * What an {@see \App\Domain\Entity\AuditEvent} records. Every action that
 * creates, reads or destroys personal data is listed here, so the trail can
 * answer "who did what to this subject, and when" (GDPR accountability).
 */
enum AuditAction: string
{
    case HolderRegistered = 'holder_registered';
    case HolderErasureRequested = 'holder_erasure_requested';
    case HolderAnonymized = 'holder_anonymized';
    case DocumentStored = 'document_stored';
    case DocumentConsulted = 'document_consulted';
}
