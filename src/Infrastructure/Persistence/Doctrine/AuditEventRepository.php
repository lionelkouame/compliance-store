<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\AuditEvent;
use App\Domain\Port\Repository\AuditEventRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditEvent>
 */
final class AuditEventRepository extends ServiceEntityRepository implements AuditEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditEvent::class);
    }

    public function add(AuditEvent $event): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($event);
        $entityManager->flush();
    }

    public function findLatest(?string $subjectId, int $limit): array
    {
        $criteria = null === $subjectId ? [] : ['subjectId' => $subjectId];

        // The id is a UUID v7 (time-ordered): it breaks ties between events of the same second.
        return $this->findBy($criteria, ['occurredAt' => 'DESC', 'id' => 'DESC'], $limit);
    }
}
