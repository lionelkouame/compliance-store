<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\IdValidatorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter validating UUID format using Symfony Uuid component.
 */
final readonly class SymfonyUuidValidator implements IdValidatorInterface
{
    public function isValid(string $id): bool
    {
        return Uuid::isValid($id);
    }
}
