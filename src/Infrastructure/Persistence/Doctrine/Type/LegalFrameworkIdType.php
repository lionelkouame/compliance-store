<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\LegalFrameworkId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class LegalFrameworkIdType extends StringType
{
    public const NAME = 'legal_framework_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LegalFrameworkId
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return LegalFrameworkId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof LegalFrameworkId) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', LegalFrameworkId::class]);
    }
}
