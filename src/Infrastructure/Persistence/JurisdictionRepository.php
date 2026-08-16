<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Jurisdiction;
use App\Domain\Exception\JurisdictionAlreadyExistsException;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionCountry;
use App\Domain\ValueObject\JurisdictionId;
use App\Domain\ValueObject\JurisdictionRegion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

    public function findAllMatching(?JurisdictionRegion $region = null, ?JurisdictionCountry $country = null, ?bool $active = null): array
    {
        $qb = $this->createQueryBuilder('j');

        if (null !== $region) {
            $qb->andWhere('j.region = :region')->setParameter('region', $region->value);
        }

        if (null !== $country) {
            $qb->andWhere('j.country = :country')->setParameter('country', $country->value);
        }

        if (null !== $active) {
            $qb->andWhere('j.active = :active')->setParameter('active', $active);
        }

        /** @var list<Jurisdiction> */
        return $qb->getQuery()->getResult();
    }

    public function existsByCode(JurisdictionCode $code): bool
    {
        return null !== $this->findOneBy(['code' => $code->value]);
    }

    /**
     * @throws JurisdictionAlreadyExistsException if a concurrent request already
     *         committed the same code between the Use Case's existsByCode() check
     *         and this flush() (TOCTOU race), caught here via the DB unique index
     *         as the last line of defense.
     */
    public function add(Jurisdiction $jurisdiction): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($jurisdiction);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw JurisdictionAlreadyExistsException::forCode($jurisdiction->code()->value);
        }
    }

    public function update(Jurisdiction $jurisdiction): void
    {
        $this->getEntityManager()->flush();
    }
}
