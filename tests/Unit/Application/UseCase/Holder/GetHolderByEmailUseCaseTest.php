<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Holder;

use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailRequest;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailUseCase;
use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use PHPUnit\Framework\TestCase;

final class GetHolderByEmailUseCaseTest extends TestCase
{
    public function testExecuteReturnsTheHolderReadModel(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findByEmail')->willReturn(Holder::create(
            HolderId::make('01a17b0e-e559-7fc6-b4e1-4b96eaa3262c'),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-01-01'),
            Email::make('jane@example.com'),
        ));

        $readModel = (new GetHolderByEmailUseCase($holders))
            ->execute(new GetHolderByEmailRequest('jane@example.com'))
            ->readModel;

        self::assertSame('01a17b0e-e559-7fc6-b4e1-4b96eaa3262c', $readModel->id->value());
        self::assertSame('Jane Doe', $readModel->name->value);
        self::assertSame('1990-01-01', (string) $readModel->birthDate);
        self::assertSame('jane@example.com', $readModel->email->value);
        self::assertSame(HolderStatus::Active, $readModel->status);
        self::assertNull($readModel->erasureRequestedAt);
    }

    public function testExecuteThrowsWhenNoHolderMatchesTheEmail(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findByEmail')->willReturn(null);

        $this->expectException(HolderNotFoundException::class);

        (new GetHolderByEmailUseCase($holders))->execute(new GetHolderByEmailRequest('nobody@example.com'));
    }

    public function testExecuteTreatsAMalformedEmailAsNotFound(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::never())->method('findByEmail');

        $this->expectException(HolderNotFoundException::class);

        (new GetHolderByEmailUseCase($holders))->execute(new GetHolderByEmailRequest('not-an-email'));
    }
}
