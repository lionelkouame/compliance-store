<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\ChangeStorageSpaceStatus;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;

/**
 * @implements UseCaseInterface<ChangeStorageSpaceStatusRequest, ChangeStorageSpaceStatusResponse>
 */
final readonly class ChangeStorageSpaceStatusUseCase implements UseCaseInterface
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private IdValidatorInterface $idValidator,
    ) {}

    public function execute(UseCaseRequestInterface $request): ChangeStorageSpaceStatusResponse
    {
        // An id that is not even a valid UUID cannot match anything: it is "not found".
        if (!$this->idValidator->isValid($request->id)) {
            throw new StorageSpaceNotFoundException();
        }

        $storageSpace = $this->storageSpaces->get(StorageSpaceId::fromString($request->id));

        if (null === $storageSpace) {
            throw new StorageSpaceNotFoundException();
        }

        $updated = $storageSpace->withStatus(StorageSpaceStatus::fromString($request->status));

        $this->storageSpaces->update($updated);

        return new ChangeStorageSpaceStatusResponse(
            InfosStorageSpace::fromEntity($updated),
        );
    }
}
