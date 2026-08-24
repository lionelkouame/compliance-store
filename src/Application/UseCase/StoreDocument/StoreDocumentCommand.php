<?php

declare(strict_types=1);

namespace App\Application\UseCase\StoreDocument;

final readonly class StoreDocumentCommand
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $ownerId,
        public string $content,
        public array $metadata = [],
    ) {}
}
