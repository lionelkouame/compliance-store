<?php

namespace App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document;

class StoreDocumentOutput
{
    public function __construct(
        public string $id,
        public string $holderId,
        public string $fileHash,
        public string $createdAt,
    ){}
}
