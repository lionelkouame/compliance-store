<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Holder;

use App\Application\UseCase\Holder\GetHolder\GetHolderRequest;
use App\Application\UseCase\Holder\GetHolder\GetHolderUseCase;
use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use PHPUnit\Framework\TestCase;

final class GetHolderUseCaseTest extends TestCase
{
    private const string ID = '01a17b0e-e559-7fc6-b4e1-4b96eaa3262c';

    public function testExecuteReturnsTheHolderReadModel(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(Holder::create(
            HolderId::make(self::ID),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-01-01'),
            Email::make('jane@example.com'),
        ));

        $readModel = $this->useCase($holders, validId: true)
            ->execute(new GetHolderRequest(self::ID))
            ->readModel;

        self::assertSame(self::ID, $readModel->id->value());
        self::assertSame('Jane Doe', $readModel->name->value);
        self::assertSame('jane@example.com', $readModel->email->value);
        self::assertSame(HolderStatus::Active, $readModel->status);
    }

    public function testExecuteThrowsWhenNoHolderMatchesTheId(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, validId: true)->execute(new GetHolderRequest(self::ID));
    }

    public function testExecuteTreatsAMalformedIdAsNotFound(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::never())->method('findById');

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, validId: false)->execute(new GetHolderRequest('not-a-uuid'));
    }

    private function useCase(HolderRepositoryInterface $holders, bool $validId): GetHolderUseCase
    {
        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn($validId);

        return new GetHolderUseCase($holders, $idValidator);
    }
}
