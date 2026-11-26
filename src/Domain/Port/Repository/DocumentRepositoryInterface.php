<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Document;
use App\Domain\ValueObject\Shared\Identifier\HolderId;

interface DocumentRepositoryInterface
{
    public function add(Document $document): void;

    /**
     * @return list<Document>
     */
    public function findByHolder(HolderId $holderId): array;

    /**
     * Supprime la ligne du document, et avec elle sa clé de données chiffrée :
     * sans elle, le contenu chiffré n'est plus déchiffrable.
     */
    public function remove(Document $document): void;
}
