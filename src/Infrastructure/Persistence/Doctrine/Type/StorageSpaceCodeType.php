<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StorageSpaceCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class StorageSpaceCodeType extends StringType
{
    public const NAME = 'storage_space_code';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?StorageSpaceCode
    {
        if (null === $value || $value instanceof StorageSpaceCode) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', StorageSpaceCode::class]);
        }

        return new StorageSpaceCode($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof StorageSpaceCode) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::class, ['null', 'string', StorageSpaceCode::class]);
    }
}
