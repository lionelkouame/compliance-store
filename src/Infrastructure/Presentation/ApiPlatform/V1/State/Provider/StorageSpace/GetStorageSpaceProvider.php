<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\StorageSpace;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\StorageSpace\GetStorageSpace\GetStorageSpaceRequest;
use App\Application\UseCase\StorageSpace\GetStorageSpace\GetStorageSpaceUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace\StorageSpaceOutput;

/**
 * @implements ProviderInterface<StorageSpaceOutput>
 */
final readonly class GetStorageSpaceProvider implements ProviderInterface
{
    public function __construct(
        private GetStorageSpaceUseCase $useCase,
    ) {}

    /**
     * @param array{id?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceOutput
    {
        $useCaseResponse = $this->useCase->execute(new GetStorageSpaceRequest(
            id: $uriVariables['id'] ?? '',
        ));

        return StorageSpaceOutput::fromReadModel($useCaseResponse->readModel);
    }
}
