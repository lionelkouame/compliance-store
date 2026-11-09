<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateStorageSpace;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;

final readonly class CreateStorageSpaceUseCase
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private GeneratorInterface $idGenerator,
    ) {}

    public function execute(CreateStorageSpaceCommand $command): StorageSpace
    {
        $code = new StorageSpaceCode($command->code);

        if (null !== $this->storageSpaces->findByCode($code)) {
            throw new StorageSpaceCodeAlreadyExistsException($code);
        }
        $storageSpaceId = StorageSpaceId::fromString($this->idGenerator->generate());
        $storageSpace = StorageSpace::create(
            id: $storageSpaceId,
            code: $code,
            name: new StorageSpaceName($command->name),
        );

        $this->storageSpaces->save($storageSpace);

        return $storageSpace;
    }
}
