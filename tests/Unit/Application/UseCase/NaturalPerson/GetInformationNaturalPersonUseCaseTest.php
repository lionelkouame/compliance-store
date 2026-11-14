<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\NaturalPerson;

use App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson\GetInformationNaturalPersonRequest;
use App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson\GetInformationNaturalPersonUseCase;
use App\Domain\Entity\NaturalPerson;
use App\Domain\Exception\NaturalPerson\NaturalPersonNotFoundException;
use App\Domain\Port\Repository\NaturalPersonRepositoryInterface;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\NaturalPerson\FirstName;
use App\Domain\ValueObject\NaturalPerson\Gender;
use App\Domain\ValueObject\NaturalPerson\LastName;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;
use PHPUnit\Framework\TestCase;

final class GetInformationNaturalPersonUseCaseTest extends TestCase
{
    private const ID = '0192f0c1-0000-7000-8000-000000000042';

    public function testExecuteReturnsTheNaturalPersonReadModel(): void
    {
        $naturalPersons = $this->createStub(NaturalPersonRepositoryInterface::class);
        $naturalPersons->method('findById')->willReturn(NaturalPerson::create(
            NaturalPersonId::make(self::ID),
            Gender::Female,
            FirstName::make('Ada'),
            LastName::make('Lovelace'),
            BirthDate::make('1990-12-10'),
        ));

        $readModel = (new GetInformationNaturalPersonUseCase($naturalPersons))
            ->execute(new GetInformationNaturalPersonRequest(self::ID))
            ->readModel;

        self::assertSame(self::ID, $readModel->id->value());
        self::assertSame(Gender::Female, $readModel->gender);
        self::assertSame('Ada', $readModel->firstName->value);
        self::assertSame('Lovelace', $readModel->lastName->value);
        self::assertSame('1990-12-10', (string) $readModel->birthDate);
    }

    public function testExecuteThrowsWhenTheNaturalPersonDoesNotExist(): void
    {
        $naturalPersons = $this->createStub(NaturalPersonRepositoryInterface::class);
        $naturalPersons->method('findById')->willReturn(null);

        $this->expectException(NaturalPersonNotFoundException::class);

        (new GetInformationNaturalPersonUseCase($naturalPersons))
            ->execute(new GetInformationNaturalPersonRequest(self::ID));
    }
}
