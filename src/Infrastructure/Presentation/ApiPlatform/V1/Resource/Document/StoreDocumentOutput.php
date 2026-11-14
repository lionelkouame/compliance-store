<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document;

final readonly class StoreDocumentOutput
{
    public function __construct(
        public string $id,
        public string $holderId,
        public string $fileHash,
        public string $createdAt,
    ) {}
}
