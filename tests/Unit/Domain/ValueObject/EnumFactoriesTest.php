<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueException;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;
use PHPUnit\Framework\TestCase;

final class EnumFactoriesTest extends TestCase
{
    public function testIdentityDocumentTypeIsBuiltFromAKnownValue(): void
    {
        self::assertSame(IdentityDocumentType::Passport, IdentityDocumentType::fromString('Passport'));
    }

    public function testIdentityDocumentTypeRejectsAnUnknownValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('must be one of: Passport, NationalCard.');

        IdentityDocumentType::fromString('DriverLicense');
    }

    public function testStorageSpaceStatusIsBuiltFromAKnownValue(): void
    {
        self::assertSame(StorageSpaceStatus::Archived, StorageSpaceStatus::fromString('archived'));
    }

    public function testStorageSpaceStatusRejectsAnUnknownValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('must be one of: active, inactive, archived.');

        StorageSpaceStatus::fromString('bogus');
    }
}
