<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StorageSpaceName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class StorageSpaceNameType extends StringType
{
    public const NAME = 'storage_space_name';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StorageSpaceName
    {
        return \is_string($value) ? new StorageSpaceName($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof StorageSpaceName ? $value->value : (\is_string($value) ? $value : null);
    }
}
