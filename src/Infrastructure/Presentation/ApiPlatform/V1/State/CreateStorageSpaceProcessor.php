<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\CreateStorageSpace\CreateStorageSpaceCommand;
use App\Application\UseCase\CreateStorageSpace\CreateStorageSpaceUseCase;
use App\Domain\Exception\StorageSpaceCodeAlreadyExistsException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\StorageSpaceResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<mixed, StorageSpaceResource>
 */
final readonly class CreateStorageSpaceProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateStorageSpaceUseCase $useCase,
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceResource
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = json_decode($request?->getContent() ?? '', true);
        $payload = \is_array($payload) ? $payload : [];

        $input = new StorageSpaceResource();
        $input->code = \is_string($payload['code'] ?? null) ? $payload['code'] : null;
        $input->name = \is_string($payload['name'] ?? null) ? $payload['name'] : null;

        $this->validator->validate($input);

        if (null === $input->code || null === $input->name) {
            throw new UnprocessableEntityHttpException('Missing required storage space parameters.');
        }

        try {
            $storageSpace = $this->useCase->execute(new CreateStorageSpaceCommand(
                code: $input->code,
                name: $input->name,
            ));
        } catch (StorageSpaceCodeAlreadyExistsException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return StorageSpaceResource::fromEntity($storageSpace);
    }
}
