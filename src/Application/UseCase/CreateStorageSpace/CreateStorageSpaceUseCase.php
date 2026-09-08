<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateStorageSpace;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\StorageSpaceIdGeneratorInterface;
use App\Domain\ValueObject\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpaceName;

final readonly class CreateStorageSpaceUseCase
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private StorageSpaceIdGeneratorInterface $idGenerator,
    ) {}

    public function execute(CreateStorageSpaceCommand $command): StorageSpace
    {
        $code = new StorageSpaceCode($command->code);

        if (null !== $this->storageSpaces->findByCode($code)) {
            throw new StorageSpaceCodeAlreadyExistsException($code);
        }

        $storageSpace = StorageSpace::create(
            id: $this->idGenerator->generate(),
            code: $code,
            name: new StorageSpaceName($command->name),
        );

        $this->storageSpaces->add($storageSpace);

        return $storageSpace;
    }
}
