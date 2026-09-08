<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\ChangeStorageSpaceStatus\ChangeStorageSpaceStatusCommand;
use App\Application\UseCase\ChangeStorageSpaceStatus\ChangeStorageSpaceStatusUseCase;
use App\Domain\Exception\StorageSpaceNotFoundException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpaceResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<mixed, StorageSpaceResource>
 */
final readonly class ChangeStorageSpaceStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private ChangeStorageSpaceStatusUseCase $useCase,
        private RequestStack $requestStack,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceResource
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = json_decode($request?->getContent() ?? '', true);
        $payload = \is_array($payload) ? $payload : [];
        $status = \is_string($payload['status'] ?? null) ? $payload['status'] : null;

        if (null === $status) {
            throw new UnprocessableEntityHttpException('A "status" field is required.');
        }

        $id = $uriVariables['id'] ?? null;

        if (!\is_string($id)) {
            throw new NotFoundHttpException('Invalid storage space id.');
        }

        try {
            $storageSpace = $this->useCase->execute(new ChangeStorageSpaceStatusCommand(
                id: $id,
                status: $status,
            ));
        } catch (StorageSpaceNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return StorageSpaceResource::fromEntity($storageSpace);
    }
}
