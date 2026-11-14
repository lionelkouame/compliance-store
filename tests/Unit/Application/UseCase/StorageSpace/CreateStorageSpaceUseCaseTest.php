<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\StorageSpace;

use App\Application\UseCase\StorageSpace\CreateStorageSpace\CreateStorageSpaceRequest;
use App\Application\UseCase\StorageSpace\CreateStorageSpace\CreateStorageSpaceUseCase;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;
use PHPUnit\Framework\TestCase;

final class CreateStorageSpaceUseCaseTest extends TestCase
{
    public function testExecuteCreatesAnActiveStorageSpace(): void
    {
        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('findByCode')->willReturn(null);
        $storageSpaces->expects(self::once())->method('save');

        $idGenerator = $this->createStub(GeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('660e8400-e29b-41d4-a716-446655440000');

        $useCase = new CreateStorageSpaceUseCase(
            storageSpaces: $storageSpaces,
            idGenerator: $idGenerator,
        );

        $readModel = $useCase->execute(new CreateStorageSpaceRequest(
            code: 'EU-WEST-1',
            name: 'EU West Primary',
        ))->readModel;

        self::assertSame('660e8400-e29b-41d4-a716-446655440000', $readModel->id->value);
        self::assertSame('EU-WEST-1', $readModel->code->value);
        self::assertSame('EU West Primary', $readModel->name->value);
        self::assertSame(StorageSpaceStatus::Active, $readModel->status);
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
        $storageSpaces->expects(self::never())->method('save');

        $idGenerator = $this->createStub(GeneratorInterface::class);

        $useCase = new CreateStorageSpaceUseCase(
            storageSpaces: $storageSpaces,
            idGenerator: $idGenerator,
        );

        $this->expectException(StorageSpaceCodeAlreadyExistsException::class);

        $useCase->execute(new CreateStorageSpaceRequest(
            code: 'EU-WEST-1',
            name: 'Another name',
        ));
    }
}
