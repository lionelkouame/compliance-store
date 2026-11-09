<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Document\FileHash;
use PHPUnit\Framework\TestCase;

final class FileHashTest extends TestCase
{
    public function testFromPlaintextComputesSha256Digest(): void
    {
        $hash = FileHash::make('hello world');

        self::assertSame(hash('sha256', 'hello world'), $hash->value());
    }

    public function testFromDigestRejectsNonHexDigest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FileHash::fromDigest('not-a-valid-hash');
    }

    public function testEqualsComparesValue(): void
    {
        $a = FileHash::make('content');
        $b = FileHash::make('content');

        self::assertTrue($a->equals($b));
    }
}
