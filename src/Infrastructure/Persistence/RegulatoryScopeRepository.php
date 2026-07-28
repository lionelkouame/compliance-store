<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\RegulatoryScope;
use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;
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

    public function findByCode(RegulatoryScopeCode $code): ?RegulatoryScope
    {
        return $this->find($code->value);
    }

    public function findActiveByCode(RegulatoryScopeCode $code): ?RegulatoryScope
    {
        return $this->findOneBy(['code' => $code->value, 'isActive' => true]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function existsByCode(RegulatoryScopeCode $code): bool
    {
        return null !== $this->find($code->value);
    }

    public function add(RegulatoryScope $regulatoryScope): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($regulatoryScope);
        $entityManager->flush();
    }
}
