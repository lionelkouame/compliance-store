<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Holder;

/**
 * Lifecycle status of a Holder, driven by the GDPR right to erasure
 * (art. 17):
 *
 * - Active: in a normal state, data is retained for its lawful purpose.
 * - ErasureRequested: the data subject asked for erasure; the request is
 *                     pending processing.
 * - Anonymized: erasure has been carried out; the Holder no longer
 *                     carries identifying data.
 */
enum HolderStatus: string
{
    case Active = 'active';
    case ErasureRequested = 'erasure_requested';
    case Anonymized = 'anonymized';
}
