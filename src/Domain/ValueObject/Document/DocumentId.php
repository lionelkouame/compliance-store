<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

use App\Domain\ValueObject\IdentifierTrait;

final readonly class DocumentId
{
    use IdentifierTrait;
}
