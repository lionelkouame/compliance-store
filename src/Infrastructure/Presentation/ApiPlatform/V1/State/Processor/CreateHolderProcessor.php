<?php

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\Holder\CreateHolder\CreateHolderRequest;
use App\Application\UseCase\Holder\CreateHolder\CreateHolderUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\CreateHolderInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\CreateHolderOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<mixed, CreateHolderOutput>
 */
final readonly class CreateHolderProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private CreateHolderUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        Assert::isInstanceOf($data, CreateHolderInput::class);

        $this->validator->validate($data);

        Assert::string($data->holderName);
        Assert::string($data->birthDate);
        Assert::string($data->email);

        $useCaseRequest = new CreateHolderRequest(
            holderName: $data->holderName,
            birthDate: $data->birthDate,
            email: $data->email,
        );

        $useCaseResponse = $this->useCase->execute($useCaseRequest);

        return new CreateHolderOutput(identifier: $useCaseResponse->holderId);
    }
}
