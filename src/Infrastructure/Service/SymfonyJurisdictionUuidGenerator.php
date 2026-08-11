<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\JurisdictionIdGeneratorInterface;
use App\Domain\ValueObject\JurisdictionId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyJurisdictionUuidGenerator implements JurisdictionIdGeneratorInterface
{
    public function generate(): JurisdictionId
    {
        return JurisdictionId::fromString(Uuid::v7()->toRfc4122());
    }
}
