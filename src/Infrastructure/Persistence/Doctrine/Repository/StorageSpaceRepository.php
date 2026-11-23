<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Exception\StorageSpace\StorageSpaceNotFoundException;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     */
    public function update(StorageSpace $storageSpace): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE storage_space SET status = :status WHERE id = :id',
            [
                'status' => $storageSpace->status->value,
                'id' => $storageSpace->id->value,
            ],
        );

        $this->getEntityManager()->clear();
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
