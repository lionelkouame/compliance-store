<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Holder;
use App\Domain\Exception\InvalidHolderStatusTransitionException;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;
use App\Domain\ValueObject\HolderStatus;
use PHPUnit\Framework\TestCase;

final class HolderTest extends TestCase
{
    public function testRegisterCreatesAnActiveHolder(): void
    {
        $holder = $this->register();

        self::assertSame(HolderStatus::Active, $holder->status());
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

        $this->register()->anonymize();
    }

    public function testAnonymizeRemovesIdentifyingDataAndStampsTheDate(): void
    {
        $registered = $this->register();
        $holder = $registered->requestErasure()->anonymize();

        self::assertSame(HolderStatus::Anonymized, $holder->status());
        self::assertSame(Holder::ANONYMIZED_NAME, (string) $holder->name());
        self::assertNull($holder->birthDate());
        self::assertNull($holder->email());
        self::assertNotNull($holder->erasureRequestedAt());
        self::assertNotNull($holder->anonymizedAt());
        // Accountability data survives.
        self::assertSame($registered->id(), $holder->id());
        self::assertSame($registered->createdAt(), $holder->createdAt());
    }

    public function testAnonymizeCannotBeAppliedTwice(): void
    {
        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->register()->requestErasure()->anonymize()->anonymize();
    }

    private function register(?\DateTimeImmutable $consentCollectedAt = null): Holder
    {
        return Holder::register(
            id: HolderId::fromString('770e8400-e29b-41d4-a716-446655440111'),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada.lovelace@example.com'),
            consentCollectedAt: $consentCollectedAt,
        );
    }
}
