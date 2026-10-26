<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\HolderId;
use PHPUnit\Framework\TestCase;

final class HolderIdTest extends TestCase
{
    public function testAcceptsAValueWithoutValidator(): void
    {
        $id = HolderId::fromString('770e8400-e29b-41d4-a716-446655440111');

        self::assertSame('770e8400-e29b-41d4-a716-446655440111', $id->value);
        self::assertTrue($id->equals(new HolderId('770e8400-e29b-41d4-a716-446655440111')));
    }

    public function testRejectsAnEmptyValueEvenWithoutValidator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HolderId('');
    }

    public function testRejectsAValueRefusedByTheValidator(): void
    {
        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        new HolderId('not-a-uuid', $validator);
    }
}
