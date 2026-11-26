<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\SymfonyUid;

use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ValueObject\Shared\Identifier\IdentityDocumentId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyIdentityDocumentUuidGenerator implements IdentityDocumentIdGeneratorInterface
{
    public function generate(): IdentityDocumentId
    {
        return IdentityDocumentId::make(Uuid::v7()->toRfc4122()); // @phpstan-ignore missingType.checkedException (a UUID v7 is always well formed)
    }
}
