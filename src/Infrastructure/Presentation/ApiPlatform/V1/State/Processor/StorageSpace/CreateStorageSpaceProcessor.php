<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\StorageSpace;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StorageSpace\CreateStorageSpace\CreateStorageSpaceRequest;
use App\Application\UseCase\StorageSpace\CreateStorageSpace\CreateStorageSpaceUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace\CreateStorageSpaceInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace\StorageSpaceOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CreateStorageSpaceInput, StorageSpaceOutput>
 */
final readonly class CreateStorageSpaceProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private CreateStorageSpaceUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceOutput
    {
        Assert::isInstanceOf($data, CreateStorageSpaceInput::class);

        $this->validator->validate($data);

        Assert::string($data->code);
        Assert::string($data->name);

        $useCaseResponse = $this->useCase->execute(new CreateStorageSpaceRequest(
            code: $data->code,
            name: $data->name,
        ));

        return StorageSpaceOutput::fromReadModel($useCaseResponse->readModel);
    }
}
