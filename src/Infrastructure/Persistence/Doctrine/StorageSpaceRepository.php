<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\StorageSpaceNotFoundException;
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

    public function save(StorageSpace $storageSpace): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($storageSpace);
        $entityManager->flush();
    }

    /**
     * StorageSpace properties are readonly, so Doctrine's unit of work can't
     * change-track it like a regular entity: the update is an explicit DQL
     * statement (table and column names come from the mapping) rather than a
     * persist() on a mutated instance.
     *
     * @throws StorageSpaceNotFoundException when no row carries this id
     */
    public function update(StorageSpace $storageSpace): void
    {
        $entityManager = $this->getEntityManager();

        $affected = $entityManager
            ->createQuery('UPDATE '.StorageSpace::class.' s SET s.status = :status WHERE s.id = :id')
            ->setParameter('status', $storageSpace->status->value)
            ->setParameter('id', $storageSpace->id->value)
            ->execute();

        if (1 !== $affected) {
            throw new StorageSpaceNotFoundException($storageSpace->id);
        }

        // Only the previously loaded instance of this aggregate is stale now:
        // drop it instead of clearing every managed entity of the request.
        $stale = $entityManager->getUnitOfWork()->tryGetById($storageSpace->id->value, StorageSpace::class);

        if ($stale instanceof StorageSpace) {
            $entityManager->detach($stale);
        }
    }

    public function get(StorageSpaceId $id): ?StorageSpace
    {
        return $this->find($id->value);
    }

    public function findByCode(StorageSpaceCode $code): ?StorageSpace
    {
        return $this->findOneBy(['code' => $code->value]);
    }
}
