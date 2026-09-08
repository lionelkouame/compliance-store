<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StorageSpaceId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class StorageSpaceIdType extends StringType
{
    public const NAME = 'storage_space_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StorageSpaceId
    {
        return \is_string($value) ? StorageSpaceId::fromString($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof StorageSpaceId ? $value->value : (\is_string($value) ? $value : null);
    }
}
