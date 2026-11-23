<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocument\IdentityDocumentAlreadyExistsException;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IdentityDocument>
 */
final class IdentityDocumentRepository extends ServiceEntityRepository implements IdentityDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdentityDocument::class);
    }

    /**
     * A concurrent insert of the same (type, country, number) passes the use
     * case check but is rejected by the unique constraint: reported as 409.
     * @throws IdentityDocumentAlreadyExistsException when the document is already registered
     */
    public function add(IdentityDocument $identityDocument): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($identityDocument);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw IdentityDocumentAlreadyExistsException::withType($identityDocument->type(), $identityDocument->country());
        }
    }

    public function get(IdentityDocumentId $id): ?IdentityDocument
    {
        return $this->find($id->value());
    }

    public function findByNumber(
        IdentityDocumentType $type,
        CountryCode $country,
        IdentityDocumentNumber $number,
    ): ?IdentityDocument {
        return $this->findOneBy([
            'type' => $type,
            'country' => $country->value,
            'number' => $number->value,
        ]);
    }
}
