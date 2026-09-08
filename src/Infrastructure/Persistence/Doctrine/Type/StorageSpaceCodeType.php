<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StorageSpaceCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class StorageSpaceCodeType extends StringType
{
    public const NAME = 'storage_space_code';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StorageSpaceCode
    {
        return \is_string($value) ? new StorageSpaceCode($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof StorageSpaceCode ? $value->value : (\is_string($value) ? $value : null);
    }
}
