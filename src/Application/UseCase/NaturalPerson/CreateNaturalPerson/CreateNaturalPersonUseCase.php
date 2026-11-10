<?php

declare(strict_types=1);

namespace App\Application\UseCase\NaturalPerson\CreateNaturalPerson;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\NaturalPerson;
use App\Domain\Port\Repository\NaturalPersonRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ReadModel\NaturalPerson\CreateNaturalPersonRead;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\NaturalPerson\FirstName;
use App\Domain\ValueObject\NaturalPerson\Gender;
use App\Domain\ValueObject\NaturalPerson\LastName;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

/**
 * @implements UseCaseInterface<CreateNaturalPersonRequest, CreateNaturalPersonResponse>
 */
final readonly class CreateNaturalPersonUseCase implements UseCaseInterface
{
    public function __construct(
        private GeneratorInterface $generatorUuid,
        private NaturalPersonRepositoryInterface $naturalPersonRepository,
    ) {}

    public function execute(UseCaseRequestInterface $request): CreateNaturalPersonResponse
    {
        $naturalPersonId = NaturalPersonId::make($this->generatorUuid->generate());
        $gender = Gender::fromNullable($request->gender);
        $firstName = FirstName::make($request->firstName);
        $lastName = LastName::make($request->lastName);
        $birthDate = BirthDate::make($request->birthday);

        $naturalPerson = NaturalPerson::create(
            $naturalPersonId,
            $gender,
            $firstName,
            $lastName,
            $birthDate,
        );

        $this->naturalPersonRepository->add($naturalPerson);

        return new CreateNaturalPersonResponse(
            new CreateNaturalPersonRead($naturalPersonId),
        );
    }
}
