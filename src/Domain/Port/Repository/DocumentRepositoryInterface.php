<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Document;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\HolderId;

interface DocumentRepositoryInterface
{
    public function add(Document $document): void;

    public function findById(DocumentId $id): ?Document;

    /**
     * @return list<Document> every document of the holder, oldest first
     */
    public function findByHolder(HolderId $holderId): array;

    /**
     * Drops the record, and with it the wrapped data key: the ciphertext
     * left in storage (if any) can no longer be decrypted (crypto-shredding).
     */
    public function remove(Document $document): void;
}
