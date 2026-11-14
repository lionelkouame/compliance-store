<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\Document;

use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * Read-only projection of a stored Document. The wrapped data key is
 * deliberately absent: it never leaves the Application layer (ADR 0002).
 */
final readonly class InfosDocument
{
    public function __construct(
        public DocumentId $id,
        public HolderId $holderId,
        public FileHash $fileHash,
        public \DateTimeImmutable $createdAt,
    ) {}
}
