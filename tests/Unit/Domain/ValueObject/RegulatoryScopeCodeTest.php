<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\RegulatoryScopeCode;
use PHPUnit\Framework\TestCase;

final class RegulatoryScopeCodeTest extends TestCase
{
    public function testIsValidReturnsTrueForValidCode(): void
    {
        self::assertTrue(RegulatoryScopeCode::isValid('KYC_INDIVIDUAL'));
        self::assertTrue(RegulatoryScopeCode::isValid('CODE123'));
    }

    public function testIsValidReturnsFalseForInvalidCode(): void
    {
        self::assertFalse(RegulatoryScopeCode::isValid('kyc-individual'));
        self::assertFalse(RegulatoryScopeCode::isValid('123_INVALID'));
        self::assertFalse(RegulatoryScopeCode::isValid(''));
    }

    public function testConstructorAcceptsValidCode(): void
    {
        $code = new RegulatoryScopeCode('KYC_INDIVIDUAL');
        self::assertSame('KYC_INDIVIDUAL', $code->value);
        self::assertSame('KYC_INDIVIDUAL', (string) $code);
    }

    public function testConstructorThrowsExceptionForInvalidCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RegulatoryScopeCode('invalid-code');
    }
}
