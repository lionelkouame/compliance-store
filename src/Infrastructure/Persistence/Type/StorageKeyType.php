<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\StorageKey;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class StorageKeyType extends StringType
{
    public const NAME = 'storage_key';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StorageKey
    {
        return \is_string($value) ? new StorageKey($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof StorageKey ? $value->value : (\is_string($value) ? $value : null);
    }
}
