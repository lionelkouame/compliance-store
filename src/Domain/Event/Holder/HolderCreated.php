<?php

declare(strict_types=1);

namespace App\Domain\Event\Holder;

use App\Domain\Entity\Holder;
use App\Domain\Event\DomainEvent;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * Raised once a new {@see Holder} has been created and persisted.
 *
 * Carries only the data a subscriber needs (identifier, email, timestamp),
 * not the aggregate, so it remains immutable and serializable.
 */
final readonly class HolderCreated implements DomainEvent
{
    public function __construct(
        private HolderId $holderId,
        private Email $email,
        private \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Convenience factory building the event from the aggregate itself.
     */
    public static function fromHolder(Holder $holder): self
    {
        return new self(
            $holder->id(),
            $holder->email(),
            $holder->createdAt(),
        );
    }

    public function holderId(): HolderId
    {
        return $this->holderId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
