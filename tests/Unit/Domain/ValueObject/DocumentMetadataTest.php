<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\DocumentMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentMetadataTest extends TestCase
{
    public function testConstructorAcceptsValidMetadata(): void
    {
        $metadata = new DocumentMetadata('FRA', 5, ['custom_key' => 'val']);

        self::assertSame('FRA', $metadata->country);
        self::assertSame(5, $metadata->retentionYears);
        self::assertTrue($metadata->has('custom_key'));
        self::assertSame('val', $metadata->get('custom_key'));
    }

    public function testConstructorRejectsInvalidCountryCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DocumentMetadata('fra', 5);
    }

    #[DataProvider('invalidRetentionYearsProvider')]
    public function testConstructorRejectsOutOfRangeRetentionYears(int $retentionYears): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DocumentMetadata('FRA', $retentionYears);
    }

    /**
     * @return iterable<string, array{0: int}>
     */
    public static function invalidRetentionYearsProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'above ceiling' => [51];
    }
}
