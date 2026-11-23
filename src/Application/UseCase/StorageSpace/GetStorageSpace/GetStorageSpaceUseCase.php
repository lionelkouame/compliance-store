<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\GetStorageSpace;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;

/**
 * @implements UseCaseInterface<GetStorageSpaceRequest, GetStorageSpaceResponse>
 */
final readonly class GetStorageSpaceUseCase implements UseCaseInterface
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @throws StorageSpaceNotFoundException when the id is malformed or matches no storage space
     */
    public function execute(UseCaseRequestInterface $request): GetStorageSpaceResponse
    {
        // An id that is not even a valid UUID cannot match anything: it is "not found".
        if (!$this->idValidator->isValid($request->id)) {
            throw new StorageSpaceNotFoundException();
        }

        $storageSpace = $this->storageSpaces->getById(StorageSpaceId::fromString($request->id)); // @phpstan-ignore missingType.checkedException (id validated above)

        return new GetStorageSpaceResponse(
            InfosStorageSpace::fromEntity($storageSpace),
        );
    }
}
