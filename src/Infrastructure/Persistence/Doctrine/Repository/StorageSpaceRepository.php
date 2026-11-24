<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\InvalidStorageSpaceStatusTransitionException;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ValueObject\Shared\Identifier\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StorageSpace>
 */
final class StorageSpaceRepository extends ServiceEntityRepository implements StorageSpaceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorageSpace::class);
    }

    /**
     * The use case checks the code beforehand, but two concurrent requests can
     * both pass that check: the unique index then rejects the second insert,
     * which is reported as the same business error (409), not as a 500.
     * @throws StorageSpaceCodeAlreadyExistsException when the code is already taken
     */
    public function add(StorageSpace $storageSpace): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($storageSpace);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw StorageSpaceCodeAlreadyExistsException::withCode($storageSpace->code);
        }
    }

    /**
     * StorageSpace properties are readonly, so Doctrine's unit of work can't
     * change-track it like a regular entity: the update is an explicit SQL
     * statement rather than a persist() on a mutated instance.
     *
     * La condition sur le statut attendu sert de verrou optimiste : si un autre
     * traitement a changé le statut depuis la lecture, aucune ligne n'est
     * modifiée et la transition est refusée.
     *
     * @throws InvalidStorageSpaceStatusTransitionException quand le statut a changé depuis la lecture
     */
    public function update(StorageSpace $storageSpace, StorageSpaceStatus $expectedStatus): void
    {
        $updated = $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE storage_space SET status = :status WHERE id = :id AND status = :expectedStatus',
            [
                'status' => $storageSpace->status->value,
                'id' => $storageSpace->id->value,
                'expectedStatus' => $expectedStatus->value,
            ],
        );

        $this->getEntityManager()->clear();

        if (0 === $updated) {
            throw InvalidStorageSpaceStatusTransitionException::concurrentChange($storageSpace->status);
        }
    }

    /**
     * @throws StorageSpaceNotFoundException when no storage space has this id
     */
    public function getById(StorageSpaceId $id): StorageSpace
    {
        return $this->find($id->value) ?? throw StorageSpaceNotFoundException::withId($id);
    }

    public function findByCode(StorageSpaceCode $code): ?StorageSpace
    {
        return $this->findOneBy(['code' => $code->value]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }
}
