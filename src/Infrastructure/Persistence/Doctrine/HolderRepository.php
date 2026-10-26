<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\ValueObject\HolderId;
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
        return $this->find($id->value);
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

    /**
     * Holder is readonly, so a lifecycle change is a new instance that the
     * unit of work cannot track: the update is an explicit DQL statement, the
     * same way {@see StorageSpaceRepository::update()} works.
     */
    public function update(Holder $holder): void
    {
        $entityManager = $this->getEntityManager();

        $affected = $entityManager
            ->createQuery(
                'UPDATE '.Holder::class.' h '
                .'SET h.name = :name, h.birthDate = :birthDate, h.email = :email, h.status = :status, '
                .'h.erasureRequestedAt = :erasureRequestedAt, h.anonymizedAt = :anonymizedAt '
                .'WHERE h.id = :id',
            )
            ->setParameter('name', $holder->name(), 'holder_name')
            ->setParameter('birthDate', $holder->birthDate(), 'birth_date')
            ->setParameter('email', $holder->email(), 'email')
            ->setParameter('status', $holder->status()->value)
            ->setParameter('erasureRequestedAt', $holder->erasureRequestedAt(), 'datetime_immutable')
            ->setParameter('anonymizedAt', $holder->anonymizedAt(), 'datetime_immutable')
            ->setParameter('id', $holder->id()->value)
            ->execute();

        if (1 !== $affected) {
            throw new HolderNotFoundException($holder->id());
        }

        // Only the previously loaded instance of this aggregate is stale now.
        $stale = $entityManager->getUnitOfWork()->tryGetById($holder->id()->value, Holder::class);

        if ($stale instanceof Holder) {
            $entityManager->detach($stale);
        }
    }
}
