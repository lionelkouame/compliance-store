<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\Document;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\HolderId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
final class DocumentRepository extends ServiceEntityRepository implements DocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function add(Document $document): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($document);
        $entityManager->flush();
    }

    public function findById(DocumentId $id): ?Document
    {
        return $this->find($id->value);
    }

    public function findByHolder(HolderId $holderId): array
    {
        return $this->findBy(['holderId' => $holderId], ['createdAt' => 'ASC', 'id' => 'ASC']);
    }

    public function remove(Document $document): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($document);
        $entityManager->flush();
    }
}
