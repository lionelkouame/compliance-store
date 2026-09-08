<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\ListStorageSpaces\ListStorageSpacesUseCase;
use App\Domain\Entity\StorageSpace;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpaceResource;

/**
 * @implements ProviderInterface<StorageSpaceResource>
 */
final readonly class ListStorageSpacesProvider implements ProviderInterface
{
    public function __construct(
        private ListStorageSpacesUseCase $useCase,
    ) {}

    /**
     * @return list<StorageSpaceResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(
            static fn (StorageSpace $storageSpace): StorageSpaceResource => StorageSpaceResource::fromEntity($storageSpace),
            $this->useCase->execute(),
        );
    }
}
