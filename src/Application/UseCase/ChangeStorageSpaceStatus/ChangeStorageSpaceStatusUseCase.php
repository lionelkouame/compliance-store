<?php

declare(strict_types=1);

namespace App\Application\UseCase\ChangeStorageSpaceStatus;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\InvalidStatusStorageException;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;


final readonly class ChangeStorageSpaceStatusUseCase
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private IdValidatorInterface $idValidator,
    ) {}

    public function execute(ChangeStorageSpaceStatusCommand $command): StorageSpace
    {
        $id = StorageSpaceId::fromString($command->id, $this->idValidator);

        $storageSpace = $this->storageSpaces->get($id);

        if (null === $storageSpace) {
            throw new StorageSpaceNotFoundException();
        }

        $status = StorageSpaceStatus::tryFrom($command->status);

        if (null === $status) {
            throw new InvalidStatusStorageException();
        }

        $updated = $storageSpace->withStatus($status);

        $this->storageSpaces->update($updated);

        return $updated;
    }
}
