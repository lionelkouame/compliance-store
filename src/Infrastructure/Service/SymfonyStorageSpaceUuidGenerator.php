<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Port\Service\StorageSpaceIdGeneratorInterface;
use App\Domain\ValueObject\StorageSpaceId;
use Symfony\Component\Uid\Uuid;

/**
 * Infrastructure adapter generating UUID v7 using Symfony Uuid component.
 */
final readonly class SymfonyStorageSpaceUuidGenerator implements StorageSpaceIdGeneratorInterface
{
    public function generate(): StorageSpaceId
    {
        return StorageSpaceId::fromString(Uuid::v7()->toRfc4122());
    }
}
