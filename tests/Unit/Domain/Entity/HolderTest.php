<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use App\Domain\ValueObject\Shared\Generic\BirthDate;
use App\Domain\ValueObject\Shared\Identifier\HolderId;
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
        $this->expectException(InvalidValueException::class);

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

    public function testAnonymizeReplacesEveryPersonalDataAndStampsTheDate(): void
    {
        $holder = $this->register(birthDate: '1990-07-14')
            ->requestErasure()
            ->anonymize();

        self::assertSame(HolderStatus::Anonymized, $holder->status());
        self::assertNotNull($holder->anonymizedAt());
        self::assertSame(Holder::ANONYMIZED_NAME, (string) $holder->name());
        self::assertSame('770e8400-e29b-41d4-a716-446655440111@anonymized.invalid', $holder->email()->value);
        // Généralisée au 1er janvier : l'âge reste exploitable, la date exacte disparaît.
        self::assertSame('1990-01-01', (string) $holder->birthDate());
    }

    public function testAnonymizeKeepsTheIdentifierAndTheErasureRequestDate(): void
    {
        $requested = $this->register()->requestErasure();
        $anonymized = $requested->anonymize();

        self::assertTrue($requested->id()->equals($anonymized->id()));
        self::assertEquals($requested->erasureRequestedAt(), $anonymized->erasureRequestedAt());
    }

    public function testAnAnonymizedHolderCannotBeAnonymizedAgain(): void
    {
        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->register()->requestErasure()->anonymize()->anonymize();
    }

    private function register(?\DateTimeImmutable $consentCollectedAt = null, string $birthDate = '1990-01-01'): Holder
    {
        return Holder::create(
            id: HolderId::make('770e8400-e29b-41d4-a716-446655440111'),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable($birthDate, new \DateTimeZone('UTC'))),
            email: new Email('ada@example.com'),
            consentCollectedAt: $consentCollectedAt,
        );
    }
}
