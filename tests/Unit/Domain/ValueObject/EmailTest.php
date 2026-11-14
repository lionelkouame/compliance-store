<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\Document\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testItAcceptsAValidEmail(): void
    {
        self::assertSame('jane.doe@example.com', Email::make('jane.doe@example.com')->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEmails(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => ['   '];
        yield 'no at sign' => ['not-an-email'];
        yield 'no domain' => ['jane@'];
        yield 'no local part' => ['@example.com'];
        yield 'surrounding spaces' => [' jane@example.com '];
        yield 'too long' => [str_repeat('a', 250).'@example.com'];
    }

    #[DataProvider('invalidEmails')]
    public function testItRejectsAnInvalidEmail(string $value): void
    {
        $this->expectException(InvalidValueException::class);

        Email::make($value);
    }
}
