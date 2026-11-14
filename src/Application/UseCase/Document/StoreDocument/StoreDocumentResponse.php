<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\Document\InfosDocument;

final readonly class StoreDocumentResponse implements UseCaseResponseInterface
{
    public function __construct(
        public InfosDocument $readModel,
    ) {}
}
