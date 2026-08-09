<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Document;

interface DocumentRepositoryInterface
{
    public function add(Document $document): void;
}
