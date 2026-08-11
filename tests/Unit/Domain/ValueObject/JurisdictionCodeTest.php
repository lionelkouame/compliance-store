<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\JurisdictionCode;
use PHPUnit\Framework\TestCase;

final class JurisdictionCodeTest extends TestCase
{
    public function testIsValidReturnsTrueForValidCode(): void
    {
        self::assertTrue(JurisdictionCode::isValid('JUR-EU-FRA'));
        self::assertTrue(JurisdictionCode::isValid('JUR-GLOBAL'));
        self::assertTrue(JurisdictionCode::isValid('JUR-US-CA'));
    }

    public function testIsValidReturnsFalseForInvalidCode(): void
    {
        self::assertFalse(JurisdictionCode::isValid('jur-eu-fra'));
        self::assertFalse(JurisdictionCode::isValid('EU-FRA'));
        self::assertFalse(JurisdictionCode::isValid(''));
    }

    public function testConstructorAcceptsValidCode(): void
    {
        $code = new JurisdictionCode('JUR-EU-FRA');
        self::assertSame('JUR-EU-FRA', $code->value);
        self::assertSame('JUR-EU-FRA', (string) $code);
    }

    public function testConstructorThrowsExceptionForInvalidCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new JurisdictionCode('invalid-code');
    }

    public function testEquals(): void
    {
        $code1 = new JurisdictionCode('JUR-EU-FRA');
        $code2 = new JurisdictionCode('JUR-EU-FRA');
        $code3 = new JurisdictionCode('JUR-EU-DEU');

        self::assertTrue($code1->equals($code2));
        self::assertFalse($code1->equals($code3));
    }
}
