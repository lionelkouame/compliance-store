<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\LegalFrameworkCode;
use PHPUnit\Framework\TestCase;

final class LegalFrameworkCodeTest extends TestCase
{
    public function testIsValidReturnsTrueForValidCode(): void
    {
        self::assertTrue(LegalFrameworkCode::isValid('FRAMEWORK-GDPR'));
        self::assertTrue(LegalFrameworkCode::isValid('FRAMEWORK-EIDAS2'));
        self::assertTrue(LegalFrameworkCode::isValid('FRAMEWORK-COMMERCIAL-CODE-FR'));
    }

    public function testIsValidReturnsFalseForInvalidCode(): void
    {
        self::assertFalse(LegalFrameworkCode::isValid('framework-gdpr'));
        self::assertFalse(LegalFrameworkCode::isValid('GDPR'));
        self::assertFalse(LegalFrameworkCode::isValid(''));
    }

    public function testConstructorAcceptsValidCode(): void
    {
        $code = new LegalFrameworkCode('FRAMEWORK-GDPR');
        self::assertSame('FRAMEWORK-GDPR', $code->value);
        self::assertSame('FRAMEWORK-GDPR', (string) $code);
    }

    public function testConstructorThrowsExceptionForInvalidCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LegalFrameworkCode('invalid-code');
    }

    public function testEquals(): void
    {
        $code1 = new LegalFrameworkCode('FRAMEWORK-GDPR');
        $code2 = new LegalFrameworkCode('FRAMEWORK-GDPR');
        $code3 = new LegalFrameworkCode('FRAMEWORK-EIDAS2');

        self::assertTrue($code1->equals($code2));
        self::assertFalse($code1->equals($code3));
    }
}
