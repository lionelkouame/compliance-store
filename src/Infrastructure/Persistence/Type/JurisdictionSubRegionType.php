<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\JurisdictionSubRegion;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class JurisdictionSubRegionType extends StringType
{
    public const NAME = 'jurisdiction_sub_region';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?JurisdictionSubRegion
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return new JurisdictionSubRegion($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof JurisdictionSubRegion) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', JurisdictionSubRegion::class]);
    }
}
