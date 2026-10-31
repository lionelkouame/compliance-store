<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

final readonly class StoreDocumentRequest
{
    public function __construct(
        public string $content,
    ) {}
}
