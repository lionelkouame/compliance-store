<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\IdGeneratorInterface;
use App\Domain\ValueObject\RegulatoryScopeId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyUuidGenerator implements IdGeneratorInterface
{
    public function generate(): RegulatoryScopeId
    {
        return RegulatoryScopeId::fromString(Uuid::v7()->toRfc4122());
    }
}
