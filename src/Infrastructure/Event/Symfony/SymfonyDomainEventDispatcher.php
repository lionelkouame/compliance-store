<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Symfony;

use App\Domain\Event\DomainEvent;
use App\Domain\Port\Event\DomainEventPublisher as DomainEventDispatcherInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;

/**
 * Adapter wiring the domain {@see DomainEventDispatcherInterface} port onto
 * Symfony's PSR-14 event dispatcher.
 *
 * Domain events are dispatched as-is: listeners subscribe to their concrete
 * class (e.g. HolderCreated), which Symfony matches by event type.
 */
final readonly class SymfonyDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(
        private PsrEventDispatcherInterface $dispatcher,
    ) {}

    public function publish(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
