<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\InvalidStorageSpaceStatusTransitionException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageSpaceTest extends TestCase
{
    public function testANewStorageSpaceIsActive(): void
    {
        self::assertSame(StorageSpaceStatus::Active, $this->storageSpace()->status);
    }

    /**
     * @return iterable<string, array{StorageSpaceStatus, StorageSpaceStatus}>
     */
    public static function allowedTransitions(): iterable
    {
        yield 'active -> inactive' => [StorageSpaceStatus::Active, StorageSpaceStatus::Inactive];
        yield 'active -> archived' => [StorageSpaceStatus::Active, StorageSpaceStatus::Archived];
        yield 'inactive -> active' => [StorageSpaceStatus::Inactive, StorageSpaceStatus::Active];
        yield 'inactive -> archived' => [StorageSpaceStatus::Inactive, StorageSpaceStatus::Archived];
    }

    #[DataProvider('allowedTransitions')]
    public function testItAllowsTheLifecycleTransitions(StorageSpaceStatus $from, StorageSpaceStatus $to): void
    {
        $storageSpace = $this->storageSpace($from);

        $updated = $storageSpace->withStatus($to);

        self::assertSame($to, $updated->status);
        self::assertSame($from, $storageSpace->status, 'The original instance is left untouched.');
        self::assertSame($storageSpace->createdAt, $updated->createdAt);
    }

    /**
     * @return iterable<string, array{StorageSpaceStatus, StorageSpaceStatus}>
     */
    public static function forbiddenTransitions(): iterable
    {
        yield 'archived -> active' => [StorageSpaceStatus::Archived, StorageSpaceStatus::Active];
        yield 'archived -> inactive' => [StorageSpaceStatus::Archived, StorageSpaceStatus::Inactive];
        yield 'archived -> archived' => [StorageSpaceStatus::Archived, StorageSpaceStatus::Archived];
        yield 'active -> active' => [StorageSpaceStatus::Active, StorageSpaceStatus::Active];
        yield 'inactive -> inactive' => [StorageSpaceStatus::Inactive, StorageSpaceStatus::Inactive];
    }

    #[DataProvider('forbiddenTransitions')]
    public function testItRejectsTheOtherTransitions(StorageSpaceStatus $from, StorageSpaceStatus $to): void
    {
        $this->expectException(InvalidStorageSpaceStatusTransitionException::class);

        $this->storageSpace($from)->withStatus($to);
    }

    private function storageSpace(StorageSpaceStatus $status = StorageSpaceStatus::Active): StorageSpace
    {
        $storageSpace = StorageSpace::create(
            id: StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440000'),
            code: new StorageSpaceCode('EU-WEST-1'),
            name: new StorageSpaceName('EU West Primary'),
        );

        return match ($status) {
            StorageSpaceStatus::Active => $storageSpace,
            StorageSpaceStatus::Inactive => $storageSpace->withStatus(StorageSpaceStatus::Inactive),
            StorageSpaceStatus::Archived => $storageSpace->withStatus(StorageSpaceStatus::Archived),
        };
    }
}
