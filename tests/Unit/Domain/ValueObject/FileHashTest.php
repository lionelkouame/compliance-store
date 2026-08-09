<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\FileHash;
use PHPUnit\Framework\TestCase;

final class FileHashTest extends TestCase
{
    public function testFromPlaintextComputesSha256Digest(): void
    {
        $hash = FileHash::fromPlaintext('hello world');

        self::assertSame(hash('sha256', 'hello world'), $hash->value);
    }

    public function testConstructorRejectsNonHexDigest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FileHash('not-a-valid-hash');
    }

    public function testEqualsComparesValue(): void
    {
        $a = FileHash::fromPlaintext('content');
        $b = FileHash::fromPlaintext('content');

        self::assertTrue($a->equals($b));
    }
}
