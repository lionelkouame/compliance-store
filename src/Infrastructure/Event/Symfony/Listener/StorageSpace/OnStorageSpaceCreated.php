<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Symfony\Listener\StorageSpace;

use App\Domain\Event\StorageSpace\StorageSpaceCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Example reaction to a newly created storage space.
 *
 * Autoconfiguration registers this listener thanks to the #[AsEventListener]
 * attribute; the method parameter type (StorageSpaceCreated) selects the event.
 * Replace the body with the real side effect (audit trail, provisioning...).
 */
final readonly class OnStorageSpaceCreated
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[AsEventListener]
    public function onStorageSpaceCreated(StorageSpaceCreated $event): void
    {
        $this->logger->info('Storage space created', [
            'storageSpaceId' => $event->storageSpaceId()->value,
            'code' => $event->code()->value,
            'occurredAt' => $event->occurredAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
