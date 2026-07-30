<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

/**
 * Domain port for validating unique entity identifiers.
 */
interface IdValidatorInterface
{
    public function isValid(string $id): bool;
}
