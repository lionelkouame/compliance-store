<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\StorageSpace;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ValueObject\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpaceId;
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

    public function add(StorageSpace $storageSpace): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($storageSpace);
        $entityManager->flush();
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

    public function get(StorageSpaceId $id): ?StorageSpace
    {
        return $this->find($id->value);
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
