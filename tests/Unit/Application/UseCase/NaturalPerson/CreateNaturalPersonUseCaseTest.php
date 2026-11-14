<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\NaturalPerson;

use App\Application\UseCase\NaturalPerson\CreateNaturalPerson\CreateNaturalPersonRequest;
use App\Application\UseCase\NaturalPerson\CreateNaturalPerson\CreateNaturalPersonUseCase;
use App\Domain\Entity\NaturalPerson;
use App\Domain\Port\Repository\NaturalPersonRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\NaturalPerson\Gender;
use PHPUnit\Framework\TestCase;

final class CreateNaturalPersonUseCaseTest extends TestCase
{
    private const ID = '0192f0c1-0000-7000-8000-000000000042';

    public function testExecutePersistsTheNaturalPersonAndReturnsItsIdentifier(): void
    {
        $naturalPersons = $this->createMock(NaturalPersonRepositoryInterface::class);
        $naturalPersons->expects(self::once())->method('add')->with(self::callback(
            static fn (NaturalPerson $person): bool => Gender::Female === $person->gender()
                && 'Ada' === $person->firstName()->value
                && 'Lovelace' === $person->lastName()->value,
        ));

        $response = $this->useCase($naturalPersons)->execute(new CreateNaturalPersonRequest(
            firstName: 'Ada',
            lastName: 'Lovelace',
            birthday: '1990-12-10',
            gender: 'female',
        ));

        self::assertSame(self::ID, $response->readModel->id->value());
    }

    public function testExecuteDefaultsToAnUnspecifiedGender(): void
    {
        $naturalPersons = $this->createMock(NaturalPersonRepositoryInterface::class);
        $naturalPersons->expects(self::once())->method('add')->with(self::callback(
            static fn (NaturalPerson $person): bool => Gender::Unspecified === $person->gender(),
        ));

        $this->useCase($naturalPersons)->execute(new CreateNaturalPersonRequest(
            firstName: 'Ada',
            lastName: 'Lovelace',
            birthday: '1990-12-10',
        ));
    }

    private function useCase(NaturalPersonRepositoryInterface $naturalPersons): CreateNaturalPersonUseCase
    {
        $generator = $this->createStub(GeneratorInterface::class);
        $generator->method('generate')->willReturn(self::ID);

        return new CreateNaturalPersonUseCase($generator, $naturalPersons);
    }
}
