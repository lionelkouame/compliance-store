<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetStorageSpace;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\StorageSpaceId;

final readonly class GetStorageSpaceUseCase
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private IdValidatorInterface $idValidator,
    ) {}

    public function execute(string $id): StorageSpace
    {
        $storageSpaceId = StorageSpaceId::fromString($id, $this->idValidator);

        $storageSpace = $this->storageSpaces->get($storageSpaceId);

        if (null === $storageSpace) {
            throw new StorageSpaceNotFoundException($storageSpaceId);
        }

        return $storageSpace;
    }
}
