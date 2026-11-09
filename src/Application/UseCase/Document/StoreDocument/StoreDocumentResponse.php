<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

final readonly class StoreDocumentResponse
{
    public function __construct(
        public string $id,
        public string $holderId,
        public string $fileHash,
        public string $wrappedDataKey,
        public string $createdAt,
    ) {}
}
