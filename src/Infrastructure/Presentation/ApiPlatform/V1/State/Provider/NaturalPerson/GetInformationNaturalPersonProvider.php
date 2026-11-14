<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\NaturalPerson;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson\GetInformationNaturalPersonRequest;
use App\Application\UseCase\NaturalPerson\GetInformationNaturalPerson\GetInformationNaturalPersonUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson\GetNaturalPersonOutput;

/**
 * @implements ProviderInterface<GetNaturalPersonOutput>
 */
final readonly class GetInformationNaturalPersonProvider implements ProviderInterface
{
    public function __construct(
        private GetInformationNaturalPersonUseCase $useCase,
    ) {}

    /**
     * @param array{id?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GetNaturalPersonOutput
    {
        $readModel = $this->useCase->execute(new GetInformationNaturalPersonRequest(
            identifier: $uriVariables['id'] ?? '',
        ))->readModel;

        return new GetNaturalPersonOutput(
            identifier: $readModel->id->value(),
            gender: $readModel->gender->value,
            firstName: $readModel->firstName->value,
            lastName: $readModel->lastName->value,
            birthDate: (string) $readModel->birthDate,
            createdAt: $readModel->createdAt->format(\DateTimeInterface::ATOM),
        );
    }
}
