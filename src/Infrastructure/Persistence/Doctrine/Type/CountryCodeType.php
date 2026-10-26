<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\CountryCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class CountryCodeType extends StringType
{
    public const NAME = 'country_code';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CountryCode
    {
        if (null === $value || $value instanceof CountryCode) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', CountryCode::class]);
        }

        return new CountryCode($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof CountryCode) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::class, ['null', 'string', CountryCode::class]);
    }
}
