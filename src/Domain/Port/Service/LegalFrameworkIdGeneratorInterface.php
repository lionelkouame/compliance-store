<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\LegalFrameworkId;

/**
 * Domain port for generating unique legal framework identifiers.
 */
interface LegalFrameworkIdGeneratorInterface
{
    public function generate(): LegalFrameworkId;
}
