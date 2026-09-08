<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\CreateStorageSpace\CreateStorageSpaceCommand;
use App\Application\UseCase\CreateStorageSpace\CreateStorageSpaceUseCase;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\StorageSpaceIdGeneratorInterface;
use App\Domain\ValueObject\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpaceId;
use App\Domain\ValueObject\StorageSpaceName;
use App\Domain\ValueObject\StorageSpaceStatus;
use PHPUnit\Framework\TestCase;

final class CreateStorageSpaceUseCaseTest extends TestCase
{
    public function testExecuteCreatesAnActiveStorageSpace(): void
    {
        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('findByCode')->willReturn(null);
        $storageSpaces->expects(self::once())->method('add');

        $idGenerator = $this->createStub(StorageSpaceIdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440000'));

        $useCase = new CreateStorageSpaceUseCase(
            storageSpaces: $storageSpaces,
            idGenerator: $idGenerator,
        );

        $storageSpace = $useCase->execute(new CreateStorageSpaceCommand(
            code: 'EU-WEST-1',
            name: 'EU West Primary',
        ));

        self::assertSame('660e8400-e29b-41d4-a716-446655440000', $storageSpace->id->value);
        self::assertSame('EU-WEST-1', $storageSpace->code->value);
        self::assertSame('EU West Primary', $storageSpace->name->value);
        self::assertSame(StorageSpaceStatus::Active, $storageSpace->status);
    }

    public function testExecuteRejectsADuplicateCode(): void
    {
        $existing = StorageSpace::create(
            id: StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440000'),
            code: new StorageSpaceCode('EU-WEST-1'),
            name: new StorageSpaceName('EU West Primary'),
        );

        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('findByCode')->willReturn($existing);
        $storageSpaces->expects(self::never())->method('add');

        $idGenerator = $this->createStub(StorageSpaceIdGeneratorInterface::class);

        $useCase = new CreateStorageSpaceUseCase(
            storageSpaces: $storageSpaces,
            idGenerator: $idGenerator,
        );

        $this->expectException(StorageSpaceCodeAlreadyExistsException::class);

        $useCase->execute(new CreateStorageSpaceCommand(
            code: 'EU-WEST-1',
            name: 'Another name',
        ));
    }
}
