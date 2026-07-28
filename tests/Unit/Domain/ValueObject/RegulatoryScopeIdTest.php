<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\RegulatoryScopeId;
use App\Infrastructure\Service\SymfonyUuidGenerator;
use App\Infrastructure\Service\SymfonyUuidValidator;
use PHPUnit\Framework\TestCase;

final class RegulatoryScopeIdTest extends TestCase
{
    public function testSymfonyUuidGeneratorGeneratesAValidUuid(): void
    {
        $generator = new SymfonyUuidGenerator();
        $id = $generator->generate();

        self::assertNotEmpty($id->value);
        self::assertSame($id->value, (string) $id);
    }

    public function testSymfonyUuidValidatorValidatesUuid(): void
    {
        $validator = new SymfonyUuidValidator();
        $uuidStr = '550e8400-e29b-41d4-a716-446655440000';
        $id = RegulatoryScopeId::fromString($uuidStr, $validator);

        self::assertSame($uuidStr, $id->value);
    }

    public function testSymfonyUuidValidatorRejectsInvalidUuid(): void
    {
        $validator = new SymfonyUuidValidator();

        $this->expectException(\InvalidArgumentException::class);
        RegulatoryScopeId::fromString('invalid-uuid', $validator);
    }

    public function testItCreatesFromValidUuidString(): void
    {
        $uuidStr = '550e8400-e29b-41d4-a716-446655440000';
        $id = RegulatoryScopeId::fromString($uuidStr);

        self::assertSame($uuidStr, $id->value);
    }

    public function testItThrowsExceptionForInvalidUuidString(): void
    {
        $validator = new SymfonyUuidValidator();
        $this->expectException(\InvalidArgumentException::class);

        RegulatoryScopeId::fromString('not-a-valid-uuid', $validator);
    }

    public function testEquals(): void
    {
        $generator = new SymfonyUuidGenerator();
        $uuidStr = '550e8400-e29b-41d4-a716-446655440000';
        $id1 = RegulatoryScopeId::fromString($uuidStr);
        $id2 = RegulatoryScopeId::fromString($uuidStr);
        $id3 = $generator->generate();

        self::assertTrue($id1->equals($id2));
        self::assertFalse($id1->equals($id3));
    }
}
