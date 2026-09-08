<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\ChangeStorageSpaceStatus\ChangeStorageSpaceStatusCommand;
use App\Application\UseCase\ChangeStorageSpaceStatus\ChangeStorageSpaceStatusUseCase;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpaceId;
use App\Domain\ValueObject\StorageSpaceName;
use App\Domain\ValueObject\StorageSpaceStatus;
use PHPUnit\Framework\TestCase;

final class ChangeStorageSpaceStatusUseCaseTest extends TestCase
{
    public function testExecuteTransitionsAnExistingStorageSpace(): void
    {
        $existing = StorageSpace::create(
            id: StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440000'),
            code: new StorageSpaceCode('EU-WEST-1'),
            name: new StorageSpaceName('EU West Primary'),
        );

        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('get')->willReturn($existing);
        $storageSpaces->expects(self::once())->method('update')
            ->with(self::callback(static fn (StorageSpace $s) => StorageSpaceStatus::Archived === $s->status));

        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn(true);

        $useCase = new ChangeStorageSpaceStatusUseCase(
            storageSpaces: $storageSpaces,
            idValidator: $idValidator,
        );

        $updated = $useCase->execute(new ChangeStorageSpaceStatusCommand(
            id: '660e8400-e29b-41d4-a716-446655440000',
            status: 'archived',
        ));

        self::assertSame(StorageSpaceStatus::Archived, $updated->status);
    }

    public function testExecuteThrowsWhenStorageSpaceDoesNotExist(): void
    {
        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('get')->willReturn(null);
        $storageSpaces->expects(self::never())->method('update');

        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn(true);

        $useCase = new ChangeStorageSpaceStatusUseCase(
            storageSpaces: $storageSpaces,
            idValidator: $idValidator,
        );

        $this->expectException(StorageSpaceNotFoundException::class);

        $useCase->execute(new ChangeStorageSpaceStatusCommand(
            id: '660e8400-e29b-41d4-a716-446655440000',
            status: 'archived',
        ));
    }

    public function testExecuteRejectsAnInvalidStatus(): void
    {
        $existing = StorageSpace::create(
            id: StorageSpaceId::fromString('660e8400-e29b-41d4-a716-446655440000'),
            code: new StorageSpaceCode('EU-WEST-1'),
            name: new StorageSpaceName('EU West Primary'),
        );

        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('get')->willReturn($existing);
        $storageSpaces->expects(self::never())->method('update');

        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn(true);

        $useCase = new ChangeStorageSpaceStatusUseCase(
            storageSpaces: $storageSpaces,
            idValidator: $idValidator,
        );

        $this->expectException(\InvalidArgumentException::class);

        $useCase->execute(new ChangeStorageSpaceStatusCommand(
            id: '660e8400-e29b-41d4-a716-446655440000',
            status: 'bogus',
        ));
    }
}
