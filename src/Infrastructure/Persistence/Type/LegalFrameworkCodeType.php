<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\LegalFrameworkCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class LegalFrameworkCodeType extends StringType
{
    public const NAME = 'legal_framework_code';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LegalFrameworkCode
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return new LegalFrameworkCode($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof LegalFrameworkCode) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', LegalFrameworkCode::class]);
    }
}
