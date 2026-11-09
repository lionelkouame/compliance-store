<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

final readonly class StoreDocumentRequest
{
    public function __construct(
        public string $content,
    ) {}
}
