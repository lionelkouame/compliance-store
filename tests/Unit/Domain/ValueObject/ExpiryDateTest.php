<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\IdentityDocument\ExpiryDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExpiryDateTest extends TestCase
{
    public function testItAcceptsAValidDateAtMidnightUtc(): void
    {
        $expiryDate = ExpiryDate::make('2035-01-31');

        self::assertSame('2035-01-31', (string) $expiryDate);
        self::assertSame('00:00:00 UTC', $expiryDate->value->format('H:i:s e'));
    }

    public function testAPastDateIsAValidExpiryDate(): void
    {
        self::assertSame('2020-01-01', (string) ExpiryDate::make('2020-01-01'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDates(): iterable
    {
        yield 'impossible day (rolled over by PHP)' => ['2035-02-30'];
        yield 'impossible month' => ['2035-13-01'];
        yield 'wrong format' => ['31/01/2035'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidDates')]
    public function testItRejectsAnInvalidDate(string $value): void
    {
        $this->expectException(InvalidValueException::class);

        ExpiryDate::make($value);
    }

    public function testADocumentIsValidThroughItsWholeExpiryDay(): void
    {
        $expiryDate = ExpiryDate::make('2035-01-31');

        self::assertFalse($expiryDate->isExpiredOn(new \DateTimeImmutable('2035-01-31 23:59:59', new \DateTimeZone('UTC'))));
        self::assertTrue($expiryDate->isExpiredOn(new \DateTimeImmutable('2035-02-01 00:00:00', new \DateTimeZone('UTC'))));
    }

    public function testTheConstructorDropsTheTimeOfDay(): void
    {
        $expiryDate = new ExpiryDate(new \DateTimeImmutable('2035-01-31 18:30:00', new \DateTimeZone('UTC')));

        self::assertTrue($expiryDate->equals(ExpiryDate::make('2035-01-31')));
        self::assertSame('00:00:00', $expiryDate->value->format('H:i:s'));
    }
}
