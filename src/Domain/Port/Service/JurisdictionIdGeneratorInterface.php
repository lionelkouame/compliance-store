<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\JurisdictionId;

/**
 * Domain port for generating unique jurisdiction identifiers.
 */
interface JurisdictionIdGeneratorInterface
{
    public function generate(): JurisdictionId;
}
