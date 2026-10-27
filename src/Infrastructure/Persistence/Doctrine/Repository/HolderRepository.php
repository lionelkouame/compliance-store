<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Holder;
use App\Domain\Entity\StorageSpace;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StorageSpace>
 */
final class HolderRepository extends ServiceEntityRepository implements HolderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Holder::class);;
    }

    public function findById(string $id): ?Holder
    {
        return $this->findOneBy(['id' => $id]);
    }
    public function findByEmail(string $email): ?Holder
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function add(Holder $holder): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($holder);
        $entityManager->flush();
    }
}
