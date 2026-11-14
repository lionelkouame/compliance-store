<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Holder;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Holder>
 */
final class HolderRepository extends ServiceEntityRepository implements HolderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Holder::class);
    }

    public function findById(HolderId $id): ?Holder
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByEmail(Email $email): ?Holder
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
