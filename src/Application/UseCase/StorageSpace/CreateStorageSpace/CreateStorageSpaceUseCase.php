<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\CreateStorageSpace;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;

/**
 * @implements UseCaseInterface<CreateStorageSpaceRequest, CreateStorageSpaceResponse>
 */
final readonly class CreateStorageSpaceUseCase implements UseCaseInterface
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private GeneratorInterface $idGenerator,
    ) {}

    public function execute(UseCaseRequestInterface $request): CreateStorageSpaceResponse
    {
        $code = new StorageSpaceCode($request->code);
        $name = new StorageSpaceName($request->name);

        if (null !== $this->storageSpaces->findByCode($code)) {
            throw new StorageSpaceCodeAlreadyExistsException();
        }

        $storageSpace = StorageSpace::create(
            id: StorageSpaceId::fromString($this->idGenerator->generate()),
            code: $code,
            name: $name,
        );

        $this->storageSpaces->save($storageSpace);

        return new CreateStorageSpaceResponse(
            InfosStorageSpace::fromEntity($storageSpace),
        );
    }
}
