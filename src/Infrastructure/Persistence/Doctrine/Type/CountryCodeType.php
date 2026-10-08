<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\CountryCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CountryCodeType extends StringType
{
    public const NAME = 'country_code';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CountryCode
    {
        return \is_string($value) ? new CountryCode($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof CountryCode ? $value->value : (\is_string($value) ? $value : null);
    }
}
