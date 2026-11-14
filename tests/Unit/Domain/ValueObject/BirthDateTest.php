<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\Generic\BirthDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BirthDateTest extends TestCase
{
    public function testItAcceptsAValidPastDate(): void
    {
        $birthDate = BirthDate::make('1990-02-28');

        self::assertSame('1990-02-28', (string) $birthDate);
        self::assertSame('00:00:00', $birthDate->value->format('H:i:s'));
    }

    public function testItAcceptsALeapDay(): void
    {
        self::assertSame('1992-02-29', (string) BirthDate::make('1992-02-29'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDates(): iterable
    {
        yield 'impossible day (rolled over by PHP)' => ['1990-02-30'];
        yield 'non-leap February 29th' => ['1991-02-29'];
        yield 'impossible month' => ['1990-13-01'];
        yield 'wrong format' => ['01/01/1990'];
        yield 'trailing garbage' => ['1990-01-01junk'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidDates')]
    public function testItRejectsAnInvalidDate(string $value): void
    {
        $this->expectException(InvalidValueException::class);

        BirthDate::make($value);
    }

    public function testItRejectsADateThatIsNotInThePast(): void
    {
        $this->expectException(InvalidValueException::class);

        BirthDate::make((new \DateTimeImmutable('today', new \DateTimeZone('UTC')))->format('Y-m-d'));
    }
}
