<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\RequestHolderErasure\RequestHolderErasureUseCase;
use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Exception\InvalidHolderStatusTransitionException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;
use App\Domain\ValueObject\HolderStatus;
use PHPUnit\Framework\TestCase;

final class RequestHolderErasureUseCaseTest extends TestCase
{
    private const HOLDER_ID = '770e8400-e29b-41d4-a716-446655440111';

    public function testExecuteFlagsAnActiveHolderAndPersistsIt(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());
        $holders->expects(self::once())->method('update')->with(
            self::callback(static fn (Holder $holder): bool => HolderStatus::ErasureRequested === $holder->status()),
        );

        $flagged = (new RequestHolderErasureUseCase($holders, $this->acceptingValidator(), $this->createStub(AuditTrailInterface::class)))->execute(self::HOLDER_ID);

        self::assertSame(HolderStatus::ErasureRequested, $flagged->status());
        self::assertNotNull($flagged->erasureRequestedAt());
    }

    public function testExecuteFailsForAnUnknownHolder(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);
        $holders->expects(self::never())->method('update');

        $this->expectException(HolderNotFoundException::class);

        (new RequestHolderErasureUseCase($holders, $this->acceptingValidator(), $this->createStub(AuditTrailInterface::class)))->execute(self::HOLDER_ID);
    }

    public function testExecuteRejectsASecondRequestWithoutPersistingAnything(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::never())->method('update');

        $this->expectException(InvalidHolderStatusTransitionException::class);

        (new RequestHolderErasureUseCase($holders, $this->acceptingValidator(), $this->createStub(AuditTrailInterface::class)))->execute(self::HOLDER_ID);
    }

    public function testExecuteRejectsAMalformedId(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::never())->method('findById');

        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);

        (new RequestHolderErasureUseCase($holders, $validator, $this->createStub(AuditTrailInterface::class)))->execute('not-a-uuid');
    }

    private function holder(): Holder
    {
        return Holder::register(
            id: HolderId::fromString(self::HOLDER_ID),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada.lovelace@example.com'),
        );
    }

    private function acceptingValidator(): IdValidatorInterface
    {
        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(true);

        return $validator;
    }
}
