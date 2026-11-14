<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use PHPUnit\Framework\TestCase;

final class StorageSpaceNameTest extends TestCase
{
    public function testAcceptsANonEmptyName(): void
    {
        $name = new StorageSpaceName('EU West Primary');

        self::assertSame('EU West Primary', $name->value);
    }

    public function testRejectsAnEmptyName(): void
    {
        $this->expectException(InvalidValueException::class);
        new StorageSpaceName('   ');
    }

    public function testRejectsANameLongerThan255Characters(): void
    {
        $this->expectException(InvalidValueException::class);
        new StorageSpaceName(str_repeat('a', 256));
    }
}
