<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\AuditAction;

/**
 * One line of the audit trail: an actor performed an action on a subject
 * (a holder or a document, referenced by identifier) at a given instant.
 * Append-only: events are never updated nor deleted by the application.
 */
final readonly class AuditEvent
{
    private function __construct(
        private string $id,
        private AuditAction $action,
        private string $subjectId,
        private string $actor,
        private \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * @param string $id        unique identifier (UUID)
     * @param string $subjectId identifier of the holder or document concerned
     * @param string $actor     who performed the action (API client name, or "system")
     */
    public static function record(string $id, AuditAction $action, string $subjectId, string $actor): self
    {
        if ('' === trim($id)) {
            throw new \InvalidArgumentException('An audit event id cannot be empty.');
        }

        if ('' === trim($subjectId)) {
            throw new \InvalidArgumentException('An audit event needs a subject.');
        }

        $actor = trim($actor);

        if ('' === $actor) {
            throw new \InvalidArgumentException('An audit event needs an actor.');
        }

        return new self(
            id: $id,
            action: $action,
            subjectId: $subjectId,
            actor: mb_substr($actor, 0, 100),
            occurredAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function action(): AuditAction
    {
        return $this->action;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function actor(): string
    {
        return $this->actor;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
