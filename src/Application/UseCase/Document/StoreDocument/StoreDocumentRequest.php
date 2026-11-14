<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

use App\Application\UseCase\UseCaseRequestInterface;

final readonly class StoreDocumentRequest implements UseCaseRequestInterface
{
    public function __construct(
        public string $holderId,
        public string $content,
    ) {}
}
