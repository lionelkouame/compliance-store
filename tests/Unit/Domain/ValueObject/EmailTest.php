<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testAcceptsAValidAddress(): void
    {
        $email = new Email('ada.lovelace@example.com');

        self::assertSame('ada.lovelace@example.com', $email->value);
        self::assertSame('ada.lovelace@example.com', (string) $email);
        self::assertTrue($email->equals(new Email('ada.lovelace@example.com')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAddresses(): iterable
    {
        yield 'empty' => ['   '];
        yield 'no at sign' => ['ada.lovelace.example.com'];
        yield 'no domain' => ['ada@'];
        yield 'too long' => [str_repeat('a', 250).'@example.com'];
    }

    #[DataProvider('invalidAddresses')]
    public function testRejectsAnInvalidAddress(string $address): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email($address);
    }
}
