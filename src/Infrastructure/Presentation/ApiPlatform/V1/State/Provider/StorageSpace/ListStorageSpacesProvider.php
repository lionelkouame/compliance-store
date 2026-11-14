<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\StorageSpace;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\StorageSpace\ListStorageSpaces\ListStorageSpacesRequest;
use App\Application\UseCase\StorageSpace\ListStorageSpaces\ListStorageSpacesUseCase;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpace\StorageSpaceOutput;

/**
 * @implements ProviderInterface<StorageSpaceOutput>
 */
final readonly class ListStorageSpacesProvider implements ProviderInterface
{
    public function __construct(
        private ListStorageSpacesUseCase $useCase,
    ) {}

    /**
     * @return list<StorageSpaceOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $useCaseResponse = $this->useCase->execute(new ListStorageSpacesRequest());

        return array_map(
            static fn (InfosStorageSpace $readModel): StorageSpaceOutput => StorageSpaceOutput::fromReadModel($readModel),
            $useCaseResponse->readModels,
        );
    }
}
