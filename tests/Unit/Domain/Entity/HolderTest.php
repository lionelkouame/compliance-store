<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use PHPUnit\Framework\TestCase;

final class HolderTest extends TestCase
{
    public function testRegisterCreatesAnActiveHolder(): void
    {
        $holder = $this->register();

        self::assertSame(HolderStatus::Active, $holder->status());
        self::assertSame('ada@example.com', (string) $holder->email());
        self::assertSame('UTC', $holder->createdAt()->getTimezone()->getName());
        self::assertNull($holder->consentCollectedAt());
        self::assertNull($holder->erasureRequestedAt());
        self::assertNull($holder->anonymizedAt());
    }

    public function testRegisterKeepsTheConsentDateWhenProvided(): void
    {
        $collectedAt = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));

        $holder = $this->register(consentCollectedAt: $collectedAt);

        self::assertSame($collectedAt, $holder->consentCollectedAt());
    }

    public function testBirthDateMustBeInThePast(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BirthDate(new \DateTimeImmutable('tomorrow', new \DateTimeZone('UTC')));
    }

    public function testRequestErasureMovesAnActiveHolderToErasureRequested(): void
    {
        $holder = $this->register()->requestErasure();

        self::assertSame(HolderStatus::ErasureRequested, $holder->status());
        self::assertNotNull($holder->erasureRequestedAt());
    }

    public function testRequestErasureIsRejectedWhenNotActive(): void
    {
        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->register()->requestErasure()->requestErasure();
    }

    public function testAnonymizeRequiresAPendingErasureRequest(): void
    {
        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->register()->anonymize(new HolderName('REDACTED'));
    }

    public function testAnonymizeReplacesTheNameAndStampsTheDate(): void
    {
        $holder = $this->register()
            ->requestErasure()
            ->anonymize(new HolderName('REDACTED'));

        self::assertSame(HolderStatus::Anonymized, $holder->status());
        self::assertSame('REDACTED', (string) $holder->name());
        self::assertNotNull($holder->anonymizedAt());
    }

    private function register(?\DateTimeImmutable $consentCollectedAt = null): Holder
    {
        return Holder::register(
            id: HolderId::fromString('770e8400-e29b-41d4-a716-446655440111'),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada@example.com'),
            consentCollectedAt: $consentCollectedAt,
        );
    }
}
