<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Entity\AuditEvent;
use App\Domain\Port\Repository\AuditEventRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\ValueObject\AuditAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

/**
 * Writes audit events on behalf of the authenticated API client. Outside a
 * request (console, tests without a token) the actor is "system".
 */
final readonly class SecurityAwareAuditTrail implements AuditTrailInterface
{
    public const string SYSTEM_ACTOR = 'system';

    public function __construct(
        private AuditEventRepositoryInterface $events,
        private Security $security,
    ) {}

    public function record(AuditAction $action, string $subjectId): void
    {
        $this->events->add(AuditEvent::record(
            id: Uuid::v7()->toRfc4122(),
            action: $action,
            subjectId: $subjectId,
            actor: $this->security->getUser()?->getUserIdentifier() ?? self::SYSTEM_ACTOR,
        ));
    }
}
