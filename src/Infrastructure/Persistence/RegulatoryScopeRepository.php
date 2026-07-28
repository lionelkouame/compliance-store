<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegulatoryScope>
 */
final class RegulatoryScopeRepository extends ServiceEntityRepository implements RegulatoryScopeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulatoryScope::class);
    }

    public function findByCode(string $code): ?RegulatoryScope
    {
        return $this->find($code);
    }

    public function findActiveByCode(string $code): ?RegulatoryScope
    {
        return $this->findOneBy(['code' => $code, 'isActive' => true]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function existsByCode(string $code): bool
    {
        return null !== $this->find($code);
    }

    public function add(RegulatoryScope $regulatoryScope): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($regulatoryScope);
        $entityManager->flush();
    }
}
