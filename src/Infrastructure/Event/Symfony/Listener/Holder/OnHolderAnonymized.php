<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Symfony\Listener\Holder;

use App\Domain\Event\Holder\HolderAnonymized;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Trace chaque anonymisation d'un titulaire. L'événement ne contient aucune
 * donnée personnelle : il peut être journalisé tel quel. À remplacer par un
 * vrai journal d'audit persistant.
 */
final readonly class OnHolderAnonymized
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[AsEventListener]
    public function onHolderAnonymized(HolderAnonymized $event): void
    {
        $this->logger->info('Holder anonymized', [
            'holderId' => $event->holderId()->value(),
            'occurredAt' => $event->occurredAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
