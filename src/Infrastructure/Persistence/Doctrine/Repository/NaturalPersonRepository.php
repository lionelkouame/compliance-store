<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\NaturalPerson;
use App\Domain\Port\Repository\NaturalPersonRepositoryInterface;
use App\Domain\ValueObject\NaturalPerson\NaturalPersonId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NaturalPerson>
 */
final class NaturalPersonRepository extends ServiceEntityRepository implements NaturalPersonRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NaturalPerson::class);
    }

    public function findById(NaturalPersonId $id): ?NaturalPerson
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function add(NaturalPerson $naturalPerson): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($naturalPerson);
        $entityManager->flush();
    }
}
