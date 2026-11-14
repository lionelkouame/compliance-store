<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\StorageSpace;

use App\Application\UseCase\StorageSpace\ListStorageSpaces\ListStorageSpacesRequest;
use App\Application\UseCase\StorageSpace\ListStorageSpaces\ListStorageSpacesUseCase;
use App\Domain\Entity\StorageSpace;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use PHPUnit\Framework\TestCase;

final class ListStorageSpacesUseCaseTest extends TestCase
{
    public function testExecuteReturnsOneReadModelPerStorageSpace(): void
    {
        $storageSpaces = $this->createStub(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('findAll')->willReturn([
            StorageSpace::create(
                id: StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440000'),
                code: new StorageSpaceCode('EU-WEST-1'),
                name: new StorageSpaceName('EU West Primary'),
            ),
            StorageSpace::create(
                id: StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440001'),
                code: new StorageSpaceCode('EU-EAST-1'),
                name: new StorageSpaceName('EU East Primary'),
            ),
        ]);

        $readModels = (new ListStorageSpacesUseCase($storageSpaces))
            ->execute(new ListStorageSpacesRequest())
            ->readModels;

        self::assertCount(2, $readModels);
        self::assertSame('EU-WEST-1', $readModels[0]->code->value);
        self::assertSame('EU-EAST-1', $readModels[1]->code->value);
    }

    public function testExecuteReturnsAnEmptyListWhenNothingIsRegistered(): void
    {
        $storageSpaces = $this->createStub(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('findAll')->willReturn([]);

        $response = (new ListStorageSpacesUseCase($storageSpaces))->execute(new ListStorageSpacesRequest());

        self::assertSame([], $response->readModels);
    }
}
