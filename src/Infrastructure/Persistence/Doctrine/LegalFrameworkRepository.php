<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\LegalFramework;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegalFramework>
 */
final class LegalFrameworkRepository extends ServiceEntityRepository implements LegalFrameworkRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalFramework::class);
    }

    public function findById(LegalFrameworkId $id): ?LegalFramework
    {
        return $this->find($id->value);
    }

    public function findByCode(LegalFrameworkCode $code): ?LegalFramework
    {
        return $this->findOneBy(['code' => $code->value]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function existsByCode(LegalFrameworkCode $code): bool
    {
        return null !== $this->findOneBy(['code' => $code->value]);
    }

    public function add(LegalFramework $legalFramework): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($legalFramework);
        $entityManager->flush();
    }

    public function update(LegalFramework $legalFramework): void
    {
        $this->getEntityManager()->flush();
    }
}
