<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Symfony\Listener\Holder;

use App\Domain\Event\Holder\HolderCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Example reaction to a newly created holder.
 *
 * Autoconfiguration registers this listener thanks to the #[AsEventListener]
 * attribute; the method parameter type (HolderCreated) selects the event.
 * Replace the body with the real side effect (audit trail, welcome flow...).
 */
final readonly class OnHolderCreated
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[AsEventListener]
    public function onHolderCreated(HolderCreated $event): void
    {
        $this->logger->info('Holder created', [
            'holderId' => (string) $event->holderId(),
            'occurredAt' => $event->occurredAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
