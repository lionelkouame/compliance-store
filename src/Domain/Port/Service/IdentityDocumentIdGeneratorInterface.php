<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\IdentityDocumentId;

/**
 * Domain port for generating unique identity document identifiers.
 */
interface IdentityDocumentIdGeneratorInterface
{
    public function generate(): IdentityDocumentId;
}
