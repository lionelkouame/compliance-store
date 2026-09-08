<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\StorageSpaceCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageSpaceCodeTest extends TestCase
{
    public function testAcceptsAValidCode(): void
    {
        $code = new StorageSpaceCode('EU-WEST_1');

        self::assertSame('EU-WEST_1', $code->value);
    }

    #[DataProvider('invalidCodeProvider')]
    public function testRejectsAnInvalidCode(string $invalid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StorageSpaceCode($invalid);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidCodeProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'lowercase' => ['eu-west-1'];
        yield 'leading separator' => ['-EU-WEST-1'];
        yield 'trailing separator' => ['EU-WEST-1-'];
        yield 'invalid character' => ['EU WEST 1'];
    }

    public function testEqualsComparesValue(): void
    {
        self::assertTrue((new StorageSpaceCode('EU-WEST-1'))->equals(new StorageSpaceCode('EU-WEST-1')));
        self::assertFalse((new StorageSpaceCode('EU-WEST-1'))->equals(new StorageSpaceCode('EU-WEST-2')));
    }
}
