<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

final readonly class StoreDocumentCommand
{
    public function __construct(
        public string $ownerId,
        public string $content,
    ) {}
}
