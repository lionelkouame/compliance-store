<?php

declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Marker contract for every business event raised by the domain.
 *
 * A domain event is an immutable record of something that already happened
 * (hence the past tense in implementors: HolderCreated, HolderAnonymized...).
 * It carries only primitive/value-object data, never the aggregate itself,
 * so it stays serializable and free of any framework dependency.
 */
interface DomainEvent
{
    /**
     * When the event occurred (UTC).
     */
    public function occurredAt(): \DateTimeImmutable;
}
