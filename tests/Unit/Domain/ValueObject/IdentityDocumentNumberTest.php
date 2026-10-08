<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\IdentityDocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentityDocumentNumberTest extends TestCase
{
    public function testAcceptsAValidNumber(): void
    {
        $number = new IdentityDocumentNumber('18AB12345');

        self::assertSame('18AB12345', $number->value);
    }

    #[DataProvider('invalidNumberProvider')]
    public function testRejectsAnInvalidNumber(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IdentityDocumentNumber($invalid);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidNumberProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'lowercase' => ['18ab12345'];
        yield 'separator' => ['18AB-12345'];
        yield 'too long' => [str_repeat('A', 21)];
    }

    public function testEqualsComparesValue(): void
    {
        self::assertTrue((new IdentityDocumentNumber('18AB12345'))->equals(new IdentityDocumentNumber('18AB12345')));
        self::assertFalse((new IdentityDocumentNumber('18AB12345'))->equals(new IdentityDocumentNumber('18AB12346')));
    }
}
