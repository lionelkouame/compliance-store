<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetStorageSpace\GetStorageSpaceUseCase;
use App\Domain\Exception\StorageSpaceNotFoundException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpaceResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<StorageSpaceResource>
 */
final readonly class GetStorageSpaceProvider implements ProviderInterface
{
    public function __construct(
        private GetStorageSpaceUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceResource
    {
        $id = $uriVariables['id'] ?? null;

        if (!\is_string($id)) {
            throw new NotFoundHttpException('Invalid storage space id.');
        }

        try {
            $storageSpace = $this->useCase->execute($id);
        } catch (StorageSpaceNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Invalid storage space id.', $e);
        }

        return StorageSpaceResource::fromEntity($storageSpace);
    }
}
