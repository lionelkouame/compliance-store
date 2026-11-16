<?php

declare(strict_types=1);

namespace App\Domain\Port\Event;

use App\Domain\Event\DomainEvent;

/**
 * Port the application uses to publish domain events.
 *
 * Kept in the domain so use cases depend on this abstraction only, never on
 * Symfony or PSR-14 directly. The infrastructure provides the adapter.
 */
interface DomainEventPublisher
{
    public function publish(DomainEvent $event): void;
}
