<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StorageSpaceId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class StorageSpaceIdType extends StringType
{
    public const NAME = 'storage_space_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StorageSpaceId
    {
        if (null === $value || $value instanceof StorageSpaceId) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', StorageSpaceId::class]);
        }

        return StorageSpaceId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof StorageSpaceId) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::class, ['null', 'string', StorageSpaceId::class]);
    }
}
