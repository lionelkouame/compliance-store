<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\CountryCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CountryCodeTest extends TestCase
{
    public function testAcceptsAValidCode(): void
    {
        $code = new CountryCode('FRA');

        self::assertSame('FRA', $code->value);
    }

    #[DataProvider('invalidCodeProvider')]
    public function testRejectsAnInvalidCode(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CountryCode($invalid);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidCodeProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'alpha-2' => ['FR'];
        yield 'too long' => ['FRAN'];
        yield 'lowercase' => ['fra'];
        yield 'digits' => ['250'];
    }

    public function testEqualsComparesValue(): void
    {
        self::assertTrue((new CountryCode('FRA'))->equals(new CountryCode('FRA')));
        self::assertFalse((new CountryCode('FRA'))->equals(new CountryCode('DEU')));
    }
}
