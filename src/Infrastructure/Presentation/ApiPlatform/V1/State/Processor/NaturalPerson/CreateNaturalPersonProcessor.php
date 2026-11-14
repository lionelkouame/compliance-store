<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\NaturalPerson\CreateNaturalPerson\CreateNaturalPersonRequest;
use App\Application\UseCase\NaturalPerson\CreateNaturalPerson\CreateNaturalPersonUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson\CreateNaturalPersonInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\NaturalPerson\CreateNaturalPersonOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<mixed, CreateNaturalPersonOutput>
 */
final readonly class CreateNaturalPersonProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private CreateNaturalPersonUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CreateNaturalPersonOutput
    {
        Assert::isInstanceOf($data, CreateNaturalPersonInput::class);

        $this->validator->validate($data);

        Assert::string($data->firstName);
        Assert::string($data->lastName);
        Assert::string($data->birthday);
        Assert::nullOrString($data->gender);

        $useCaseRequest = new CreateNaturalPersonRequest(
            firstName: $data->firstName,
            lastName: $data->lastName,
            birthday: $data->birthday,
            gender: $data->gender,
        );

        $useCaseResponse = $this->useCase->execute($useCaseRequest);

        return new CreateNaturalPersonOutput(
            identifier: $useCaseResponse->readModel->id->value(),
        );
    }
}
