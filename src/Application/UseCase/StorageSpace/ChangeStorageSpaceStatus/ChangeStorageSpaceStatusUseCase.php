<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\ChangeStorageSpaceStatus;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Exception\StorageSpace\InvalidStorageSpaceStatusTransitionException;
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

    /**
     * @throws StorageSpaceNotFoundException when the id is malformed or matches no storage space
     * @throws InvalidValueException when the status is unknown
     * @throws InvalidStorageSpaceStatusTransitionException when the transition is not allowed, or the status changed concurrently
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): ChangeStorageSpaceStatusResponse
    {
        // An id that is not even a valid UUID cannot match anything: it is "not found".
        if (!$this->idValidator->isValid($registerIdentityDocumentRequest->id)) {
            throw new StorageSpaceNotFoundException();
        }

        $storageSpace = $this->storageSpaces->getById(StorageSpaceId::fromString($registerIdentityDocumentRequest->id));

        $updated = $storageSpace->withStatus(StorageSpaceStatus::fromString($registerIdentityDocumentRequest->status));

        // Statut lu en base : l'écriture est refusée si un autre traitement l'a changé entre-temps.
        $this->storageSpaces->update($updated, expectedStatus: $storageSpace->status);

        return new ChangeStorageSpaceStatusResponse(
            InfosStorageSpace::fromEntity($updated),
        );
    }
}
