<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\LegalFrameworkOfficialReference;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class LegalFrameworkOfficialReferenceType extends StringType
{
    public const NAME = 'legal_framework_official_reference';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LegalFrameworkOfficialReference
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return new LegalFrameworkOfficialReference($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof LegalFrameworkOfficialReference) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', LegalFrameworkOfficialReference::class]);
    }
}
