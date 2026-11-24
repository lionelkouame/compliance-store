<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Holder;

use App\Application\UseCase\Holder\RequestHolderErasure\RequestHolderErasureRequest;
use App\Application\UseCase\Holder\RequestHolderErasure\RequestHolderErasureUseCase;
use App\Domain\Entity\Holder;
use App\Domain\Event\Holder\HolderErasureRequested;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Port\Event\DomainEventPublisher;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use App\Domain\ValueObject\Shared\Generic\BirthDate;
use App\Domain\ValueObject\Shared\Identifier\HolderId;
use PHPUnit\Framework\TestCase;

final class RequestHolderErasureUseCaseTest extends TestCase
{
    private const string ID = '01a17b0e-e559-7fc6-b4e1-4b96eaa3262c';

    public function testExecuteRecordsTheRequestAndPublishesAnEvent(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());
        $holders->expects(self::once())
            ->method('update')
            ->with(
                self::callback(static fn (Holder $holder): bool => HolderStatus::ErasureRequested === $holder->status()),
                HolderStatus::Active, // l'état lu, que l'UPDATE conditionnel doit retrouver en base
            );

        $publisher = $this->createMock(DomainEventPublisher::class);
        $publisher->expects(self::once())
            ->method('publish')
            ->with(self::callback(static fn (object $event): bool => $event instanceof HolderErasureRequested
                && self::ID === $event->holderId()->value()));

        $readModel = $this->useCase($holders, $publisher)->execute(new RequestHolderErasureRequest(self::ID))->readModel;

        self::assertSame(HolderStatus::ErasureRequested, $readModel->status);
        self::assertNotNull($readModel->erasureRequestedAt);
    }

    public function testExecuteRejectsASecondRequest(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::never())->method('update');

        $publisher = $this->createMock(DomainEventPublisher::class);
        $publisher->expects(self::never())->method('publish');

        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->useCase($holders, $publisher)->execute(new RequestHolderErasureRequest(self::ID));
    }

    public function testExecuteThrowsWhenNoHolderMatchesTheId(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, $this->createStub(DomainEventPublisher::class))->execute(new RequestHolderErasureRequest(self::ID));
    }

    public function testExecuteTreatsAMalformedIdAsNotFound(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::never())->method('findById');

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, $this->createStub(DomainEventPublisher::class), validId: false)
            ->execute(new RequestHolderErasureRequest('not-a-uuid'));
    }

    private function useCase(HolderRepositoryInterface $holders, DomainEventPublisher $publisher, bool $validId = true): RequestHolderErasureUseCase
    {
        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn($validId);

        return new RequestHolderErasureUseCase($holders, $idValidator, $publisher);
    }

    private function holder(): Holder
    {
        return Holder::create(
            HolderId::make(self::ID),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-07-14'),
            Email::make('jane@example.com'),
        );
    }
}
