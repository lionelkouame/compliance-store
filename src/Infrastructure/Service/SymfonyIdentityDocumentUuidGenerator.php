<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\IdentityDocumentIdGeneratorInterface;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyIdentityDocumentUuidGenerator implements IdentityDocumentIdGeneratorInterface
{
    public function generate(): IdentityDocumentId
    {
        return IdentityDocumentId::make(Uuid::v7()->toRfc4122());
    }
}
