<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\StorageSpace;

use App\Application\UseCase\StorageSpace\GetStorageSpace\GetStorageSpaceRequest;
use App\Application\UseCase\StorageSpace\GetStorageSpace\GetStorageSpaceUseCase;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;
use PHPUnit\Framework\TestCase;

final class GetStorageSpaceUseCaseTest extends TestCase
{
    private const ID = '660e8400-e29b-41d4-a716-446655440000';

    public function testExecuteReturnsTheStorageSpaceReadModel(): void
    {
        $storageSpaces = $this->createStub(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('get')->willReturn(StorageSpace::create(
            id: StorageSpaceId::fromString(self::ID),
            code: new StorageSpaceCode('EU-WEST-1'),
            name: new StorageSpaceName('EU West Primary'),
        ));

        $readModel = $this->useCase($storageSpaces, validId: true)
            ->execute(new GetStorageSpaceRequest(self::ID))
            ->readModel;

        self::assertSame(self::ID, $readModel->id->value);
        self::assertSame('EU-WEST-1', $readModel->code->value);
        self::assertSame('EU West Primary', $readModel->name->value);
        self::assertSame(StorageSpaceStatus::Active, $readModel->status);
    }

    public function testExecuteThrowsWhenStorageSpaceDoesNotExist(): void
    {
        $storageSpaces = $this->createStub(StorageSpaceRepositoryInterface::class);
        $storageSpaces->method('get')->willReturn(null);

        $this->expectException(StorageSpaceNotFoundException::class);

        $this->useCase($storageSpaces, validId: true)->execute(new GetStorageSpaceRequest(self::ID));
    }

    public function testExecuteTreatsAMalformedIdAsNotFound(): void
    {
        $storageSpaces = $this->createMock(StorageSpaceRepositoryInterface::class);
        $storageSpaces->expects(self::never())->method('get');

        $this->expectException(StorageSpaceNotFoundException::class);

        $this->useCase($storageSpaces, validId: false)->execute(new GetStorageSpaceRequest('not-a-uuid'));
    }

    private function useCase(StorageSpaceRepositoryInterface $storageSpaces, bool $validId): GetStorageSpaceUseCase
    {
        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn($validId);

        return new GetStorageSpaceUseCase(
            storageSpaces: $storageSpaces,
            idValidator: $idValidator,
        );
    }
}
