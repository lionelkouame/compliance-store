<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

final readonly class StoreDocumentCommand
{
    public function __construct(
        public string $documentType,
        public string $ownerId,
        public string $country,
        public int $retentionYears,
        public string $content,
    ) {}
}
