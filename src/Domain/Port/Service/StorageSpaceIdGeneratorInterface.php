<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

use App\Domain\ValueObject\StorageSpaceId;

/**
 * Domain port for generating unique storage space identifiers.
 */
interface StorageSpaceIdGeneratorInterface
{
    public function generate(): StorageSpaceId;
}
