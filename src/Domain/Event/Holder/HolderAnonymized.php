<?php

declare(strict_types=1);

namespace App\Domain\Event\Holder;

use App\Domain\Entity\Holder;
use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * Émis une fois le titulaire anonymisé et ses documents supprimés.
 *
 * Ne transporte que l'identifiant et la date : aucune donnée personnelle,
 * pour que l'événement puisse être journalisé ou transmis sans risque.
 */
final readonly class HolderAnonymized implements DomainEvent
{
    public function __construct(
        private HolderId $holderId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * @throws \LogicException quand le titulaire n'est pas dans l'état attendu
     */
    public static function fromHolder(Holder $holder): self
    {
        return new self(
            $holder->id(),
            $holder->anonymizedAt() ?? throw new \LogicException('The holder has not been anonymized.'),
        );
    }

    public function holderId(): HolderId
    {
        return $this->holderId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
