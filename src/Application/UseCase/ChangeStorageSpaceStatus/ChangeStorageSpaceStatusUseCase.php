<?php

declare(strict_types=1);

namespace App\Application\UseCase\ChangeStorageSpaceStatus;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\StorageSpaceId;
use App\Domain\ValueObject\StorageSpaceStatus;

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
            throw new StorageSpaceNotFoundException($id);
        }

        $status = StorageSpaceStatus::tryFrom($command->status);

        if (null === $status) {
            throw new \InvalidArgumentException(\sprintf(
                'The status "%s" is not valid. Expected one of: %s.',
                $command->status,
                implode(', ', array_column(StorageSpaceStatus::cases(), 'value')),
            ));
        }

        $updated = $storageSpace->withStatus($status);

        $this->storageSpaces->update($updated);

        return $updated;
    }
}
