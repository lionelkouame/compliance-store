<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\DocumentIdGeneratorInterface;
use App\Domain\ValueObject\DocumentId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyDocumentUuidGenerator implements DocumentIdGeneratorInterface
{
    public function generate(): DocumentId
    {
        return DocumentId::fromString(Uuid::v7()->toRfc4122());
    }
}
