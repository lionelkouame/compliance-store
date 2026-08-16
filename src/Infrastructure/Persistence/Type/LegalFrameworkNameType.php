<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\LegalFrameworkName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class LegalFrameworkNameType extends StringType
{
    public const NAME = 'legal_framework_name';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LegalFrameworkName
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return new LegalFrameworkName($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof LegalFrameworkName) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', LegalFrameworkName::class]);
    }
}
