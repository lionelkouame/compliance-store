<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\LegalFrameworkIdGeneratorInterface;
use App\Domain\ValueObject\LegalFrameworkId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyLegalFrameworkUuidGenerator implements LegalFrameworkIdGeneratorInterface
{
    public function generate(): LegalFrameworkId
    {
        return LegalFrameworkId::fromString(Uuid::v7()->toRfc4122());
    }
}
