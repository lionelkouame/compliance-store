<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\StorageKey;
use PHPUnit\Framework\TestCase;

final class StorageKeyTest extends TestCase
{
    public function testForDocumentBuildsAPathFromTheDocumentId(): void
    {
        $id = DocumentId::fromString('550e8400-e29b-41d4-a716-446655440000');

        $key = StorageKey::forDocument($id);

        self::assertSame('documents/550e8400-e29b-41d4-a716-446655440000', $key->value);
    }

    public function testConstructorRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StorageKey('  ');
    }
}
