<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\HolderName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class HolderNameType extends StringType
{
    public const NAME = 'holder_name';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HolderName
    {
        if (null === $value || $value instanceof HolderName) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', HolderName::class]);
        }

        return new HolderName($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof HolderName) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::class, ['null', 'string', HolderName::class]);
    }
}
