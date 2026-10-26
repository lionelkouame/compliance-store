<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetDocument;

use App\Domain\Entity\Document;
use App\Domain\Exception\DocumentNotFoundException;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\DocumentId;

/**
 * Metadata of a stored document (hash, holder, date). The content itself is
 * served by {@see \App\Application\UseCase\ConsultDocument\ConsultDocumentUseCase}.
 */
final readonly class GetDocumentUseCase
{
    public function __construct(
        private DocumentRepositoryInterface $documents,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @throws \InvalidArgumentException when the id is not a valid UUID
     * @throws DocumentNotFoundException
     */
    public function execute(string $id): Document
    {
        $documentId = DocumentId::fromString($id, $this->idValidator);

        $document = $this->documents->findById($documentId);

        if (null === $document) {
            throw new DocumentNotFoundException($documentId);
        }

        return $document;
    }
}
