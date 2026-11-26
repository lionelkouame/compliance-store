<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\CreateStorageSpace;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\StorageSpace;
use App\Domain\Event\StorageSpace\StorageSpaceCreated;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Event\DomainEventPublisher;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ReadModel\StorageSpace\InfosStorageSpace;
use App\Domain\ValueObject\Shared\Identifier\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;

/**
 * @implements UseCaseInterface<CreateStorageSpaceRequest, CreateStorageSpaceResponse>
 */
final readonly class CreateStorageSpaceUseCase implements UseCaseInterface
{
    public function __construct(
        private StorageSpaceRepositoryInterface $storageSpaces,
        private GeneratorInterface $idGenerator,
        private DomainEventPublisher $domainEventPublisher,
    ) {}

    /**
     * @throws StorageSpaceCodeAlreadyExistsException when the code is already taken
     * @throws InvalidValueException when the code or the name is invalid
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): CreateStorageSpaceResponse
    {
        $code = new StorageSpaceCode($registerIdentityDocumentRequest->code);
        $name = new StorageSpaceName($registerIdentityDocumentRequest->name);

        if (null !== $this->storageSpaces->findByCode($code)) {
            throw StorageSpaceCodeAlreadyExistsException::withCode($code);
        }

        $storageSpace = StorageSpace::create(
            id: StorageSpaceId::fromString($this->idGenerator->generate()),
            code: $code,
            name: $name,
        );

        $this->storageSpaces->add($storageSpace);

        $this->domainEventPublisher->publish(
            StorageSpaceCreated::fromStorageSpace($storageSpace),
        );

        return new CreateStorageSpaceResponse(
            InfosStorageSpace::fromEntity($storageSpace),
        );
    }
}
