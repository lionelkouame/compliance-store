<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\RegulatoryScopeId;

/**
 * Domain port for generating unique entity identifiers.
 */
interface IdGeneratorInterface
{
    public function generate(): RegulatoryScopeId;
}
