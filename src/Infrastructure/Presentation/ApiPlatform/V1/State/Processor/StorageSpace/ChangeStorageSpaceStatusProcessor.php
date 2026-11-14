<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\StorageSpace;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StorageSpace\ChangeStorageSpaceStatus\ChangeStorageSpaceStatusRequest;
use App\Application\UseCase\StorageSpace\ChangeStorageSpaceStatus\ChangeStorageSpaceStatusUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace\ChangeStorageSpaceStatusInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace\StorageSpaceOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<ChangeStorageSpaceStatusInput, StorageSpaceOutput>
 */
final readonly class ChangeStorageSpaceStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private ChangeStorageSpaceStatusUseCase $useCase,
    ) {}

    /**
     * @param array{id?: string} $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceOutput
    {
        Assert::isInstanceOf($data, ChangeStorageSpaceStatusInput::class);

        $this->validator->validate($data);

        Assert::string($data->status);

        $useCaseResponse = $this->useCase->execute(new ChangeStorageSpaceStatusRequest(
            id: $uriVariables['id'] ?? '',
            status: $data->status,
        ));

        return StorageSpaceOutput::fromReadModel($useCaseResponse->readModel);
    }
}
