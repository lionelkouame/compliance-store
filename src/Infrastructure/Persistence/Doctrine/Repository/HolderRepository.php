<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderStatus;
use App\Domain\ValueObject\Shared\Identifier\HolderId;
use App\Infrastructure\Persistence\Doctrine\Type\BirthDateType;
use App\Infrastructure\Persistence\Doctrine\Type\EmailType;
use App\Infrastructure\Persistence\Doctrine\Type\HolderIdType;
use App\Infrastructure\Persistence\Doctrine\Type\HolderNameType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
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

    /**
     * A concurrent insert of the same email passes the use case check but is
     * rejected by the unique index on holder.email: reported as 409.
     * @throws HolderAlreadyExistsException when the email is already taken
     */
    public function add(Holder $holder): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($holder);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new HolderAlreadyExistsException();
        }
    }

    /**
     * Holder est immuable : l'unité de travail de Doctrine ne peut pas suivre
     * ses changements. La mise à jour est donc une requête SQL explicite,
     * comme pour StorageSpace, puis l'unité de travail est vidée pour qu'une
     * lecture suivante recharge la nouvelle version.
     *
     * La condition sur le statut attendu sert de verrou optimiste : si un autre
     * traitement a changé le statut depuis la lecture, aucune ligne n'est
     * modifiée et la transition est refusée.
     *
     * @throws InvalidHolderStatusTransitionException quand l'état a changé depuis la lecture
     */
    public function update(Holder $holder, HolderStatus $expectedStatus): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $updated = $connection->executeStatement(
            'UPDATE holder
                SET name = :name, email = :email, birth_date = :birthDate, status = :status,
                    erasure_requested_at = :erasureRequestedAt, anonymized_at = :anonymizedAt
              WHERE id = :id AND status = :expectedStatus',
            [
                'id' => $holder->id(),
                'name' => $holder->name(),
                'email' => $holder->email(),
                'birthDate' => $holder->birthDate(),
                'status' => $holder->status()->value,
                'erasureRequestedAt' => $holder->erasureRequestedAt(),
                'anonymizedAt' => $holder->anonymizedAt(),
                'expectedStatus' => $expectedStatus->value,
            ],
            [
                'id' => HolderIdType::NAME,
                'name' => HolderNameType::NAME,
                'email' => EmailType::NAME,
                'birthDate' => BirthDateType::NAME,
                'erasureRequestedAt' => Types::DATETIME_IMMUTABLE,
                'anonymizedAt' => Types::DATETIME_IMMUTABLE,
            ],
        );

        $this->getEntityManager()->clear();

        if (0 === $updated) {
            throw InvalidHolderStatusTransitionException::concurrentChange($holder->status());
        }
    }
}
