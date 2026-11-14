<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Holder;

use App\Application\UseCase\Holder\CreateHolder\CreateHolderRequest;
use App\Application\UseCase\Holder\CreateHolder\CreateHolderUseCase;
use App\Domain\Entity\Holder;
use App\Domain\Exception\AlreadyExistException;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use PHPUnit\Framework\TestCase;

final class CreateHolderUseCaseTest extends TestCase
{
    private const ID = '01a17b0e-e559-7fc6-b4e1-4b96eaa3262c';

    public function testExecuteCreatesAHolderAndReturnsItsIdentifier(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findByEmail')->willReturn(null);
        $holders->expects(self::once())->method('add');

        $response = $this->useCase($holders)->execute($this->request());

        self::assertSame(self::ID, $response->readModel->id->value());
    }

    public function testExecuteRejectsADuplicateEmail(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findByEmail')->willReturn(Holder::create(
            HolderId::make(self::ID),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-01-01'),
            Email::make('jane@example.com'),
        ));
        $holders->expects(self::never())->method('add');

        $this->expectException(HolderAlreadyExistsException::class);

        $this->useCase($holders)->execute($this->request());
    }

    public function testADuplicateEmailBelongsToTheAlreadyExistCategory(): void
    {
        self::assertInstanceOf(AlreadyExistException::class, new HolderAlreadyExistsException());
    }

    private function useCase(HolderRepositoryInterface $holders): CreateHolderUseCase
    {
        $generator = $this->createStub(GeneratorInterface::class);
        $generator->method('generate')->willReturn(self::ID);

        return new CreateHolderUseCase($generator, $holders);
    }

    private function request(): CreateHolderRequest
    {
        return new CreateHolderRequest(
            holderName: 'Jane Doe',
            birthDate: '1990-01-01',
            email: 'jane@example.com',
        );
    }
}
