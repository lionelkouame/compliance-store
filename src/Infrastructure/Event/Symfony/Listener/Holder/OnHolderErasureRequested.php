<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Symfony\Listener\Holder;

use App\Domain\Event\Holder\HolderErasureRequested;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Trace chaque demande d'effacement d'un titulaire. L'événement ne contient aucune
 * donnée personnelle : il peut être journalisé tel quel. À remplacer par un
 * vrai journal d'audit persistant.
 */
final readonly class OnHolderErasureRequested
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[AsEventListener]
    public function onHolderErasureRequested(HolderErasureRequested $event): void
    {
        $this->logger->info('Holder erasure requested', [
            'holderId' => $event->holderId()->value(),
            'occurredAt' => $event->occurredAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
