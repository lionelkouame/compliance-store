<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\GeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 strings using the Symfony Uuid component.
 */
final readonly class SymfonyUuidGenerator implements GeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
