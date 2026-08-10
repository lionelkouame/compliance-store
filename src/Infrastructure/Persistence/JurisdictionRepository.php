<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Jurisdiction>
 */
final class JurisdictionRepository extends ServiceEntityRepository implements JurisdictionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jurisdiction::class);
    }

    public function findById(JurisdictionId $id): ?Jurisdiction
    {
        return $this->find($id->value);
    }

    public function findByCode(JurisdictionCode $code): ?Jurisdiction
    {
        return $this->findOneBy(['code' => $code->value]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function existsByCode(JurisdictionCode $code): bool
    {
        return null !== $this->findOneBy(['code' => $code->value]);
    }

    public function add(Jurisdiction $jurisdiction): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($jurisdiction);
        $entityManager->flush();
    }

    public function update(Jurisdiction $jurisdiction): void
    {
        $this->getEntityManager()->flush();
    }
}
