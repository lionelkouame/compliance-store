<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\HolderName;
use PHPUnit\Framework\TestCase;

final class HolderNameTest extends TestCase
{
    public function testAcceptsANonEmptyName(): void
    {
        $name = new HolderName('Ada Lovelace');

        self::assertSame('Ada Lovelace', $name->value);
        self::assertSame('Ada Lovelace', (string) $name);
    }

    public function testStoresTheTrimmedValue(): void
    {
        $name = new HolderName('  Ada Lovelace  ');

        self::assertSame('Ada Lovelace', $name->value);
        self::assertTrue($name->equals(new HolderName('Ada Lovelace')));
    }

    public function testRejectsAnEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HolderName('   ');
    }

    public function testRejectsANameLongerThan255Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HolderName(str_repeat('a', 256));
    }

    public function testAcceptsA255CharacterNamePaddedWithSpaces(): void
    {
        $name = new HolderName(' '.str_repeat('a', 255).' ');

        self::assertSame(255, mb_strlen($name->value));
    }
}
