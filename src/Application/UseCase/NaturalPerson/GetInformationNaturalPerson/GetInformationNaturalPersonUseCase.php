<?php

declare(strict_types=1);

namespace App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Exception\NaturalPerson\NaturalPersonNotFoundException;
use App\Domain\Port\Repository\NaturalPersonRepositoryInterface;
use App\Domain\ReadModel\NaturalPerson\InfosNaturalPerson;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;

/**
 * @implements UseCaseInterface<GetInformationNaturalPersonRequest, GetInformationNaturalPersonResponse>
 */
final readonly class GetInformationNaturalPersonUseCase implements UseCaseInterface
{
    public function __construct(
        private NaturalPersonRepositoryInterface $naturalPersonRepository,
    ) {}

    /**
     * @throws NaturalPersonNotFoundException when no natural person has this id
     * @throws InvalidValueException when the identifier is malformed
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): GetInformationNaturalPersonResponse
    {
        $naturalPerson = $this->naturalPersonRepository->findById(
            NaturalPersonId::make($registerIdentityDocumentRequest->identifier),
        );

        if (null === $naturalPerson) {
            throw new NaturalPersonNotFoundException();
        }

        return new GetInformationNaturalPersonResponse(new InfosNaturalPerson(
            id: $naturalPerson->id(),
            gender: $naturalPerson->gender(),
            firstName: $naturalPerson->firstName(),
            lastName: $naturalPerson->lastName(),
            birthDate: $naturalPerson->birthDate(),
            createdAt: $naturalPerson->createdAt(),
        ));
    }
}
