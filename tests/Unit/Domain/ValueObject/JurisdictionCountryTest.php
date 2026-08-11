<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\JurisdictionCountry;
use PHPUnit\Framework\TestCase;

final class JurisdictionCountryTest extends TestCase
{
    public function testConstructorAcceptsValidAlpha3Code(): void
    {
        $country = new JurisdictionCountry('FRA');
        self::assertSame('FRA', $country->value);
        self::assertSame('FRA', (string) $country);
    }

    public function testConstructorThrowsExceptionForAlpha2Code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new JurisdictionCountry('FR');
    }

    public function testConstructorThrowsExceptionForLowercaseCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new JurisdictionCountry('fra');
    }

    public function testEquals(): void
    {
        $country1 = new JurisdictionCountry('FRA');
        $country2 = new JurisdictionCountry('FRA');
        $country3 = new JurisdictionCountry('DEU');

        self::assertTrue($country1->equals($country2));
        self::assertFalse($country1->equals($country3));
    }
}
