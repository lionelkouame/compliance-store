<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListHolderDocuments;

use App\Domain\Entity\Document;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\HolderId;

final readonly class ListHolderDocumentsUseCase
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private DocumentRepositoryInterface $documents,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @return list<Document> oldest first; empty for a holder without documents
     *
     * @throws \InvalidArgumentException when the id is not a valid UUID
     * @throws HolderNotFoundException  an unknown holder is a 404, not an empty list
     */
    public function execute(string $holderId): array
    {
        $id = HolderId::fromString($holderId, $this->idValidator);

        if (null === $this->holders->findById($id)) {
            throw new HolderNotFoundException($id);
        }

        return $this->documents->findByHolder($id);
    }
}
