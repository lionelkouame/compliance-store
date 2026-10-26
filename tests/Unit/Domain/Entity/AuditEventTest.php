<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\AuditEvent;
use App\Domain\ValueObject\AuditAction;
use PHPUnit\Framework\TestCase;

final class AuditEventTest extends TestCase
{
    public function testRecordKeepsActionSubjectActorAndStampsUtcTime(): void
    {
        $event = AuditEvent::record('00000000-0000-7000-8000-000000000001', AuditAction::DocumentStored, '660e8400-e29b-41d4-a716-446655440000', ' back-office ');

        self::assertSame(AuditAction::DocumentStored, $event->action());
        self::assertSame('660e8400-e29b-41d4-a716-446655440000', $event->subjectId());
        self::assertSame('back-office', $event->actor());
        self::assertSame('UTC', $event->occurredAt()->getTimezone()->getName());
    }

    public function testRecordTruncatesAnOverlongActor(): void
    {
        $event = AuditEvent::record('00000000-0000-7000-8000-000000000001', AuditAction::HolderRegistered, 'subject', str_repeat('a', 150));

        self::assertSame(100, mb_strlen($event->actor()));
    }

    public function testRecordRejectsAnEmptyActor(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AuditEvent::record('00000000-0000-7000-8000-000000000001', AuditAction::HolderRegistered, 'subject', '  ');
    }

    public function testRecordRejectsAnEmptySubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AuditEvent::record('00000000-0000-7000-8000-000000000001', AuditAction::HolderRegistered, '', 'system');
    }
}
