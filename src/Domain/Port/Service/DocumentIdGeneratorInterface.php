<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\DocumentId;

/**
 * Domain port for generating unique document identifiers.
 */
interface DocumentIdGeneratorInterface
{
    public function generate(): DocumentId;
}
