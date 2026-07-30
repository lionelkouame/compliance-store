<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\RegulatoryScopeCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class RegulatoryScopeCodeType extends StringType
{
    public const NAME = 'regulatory_scope_code';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RegulatoryScopeCode
    {
        return null === $value ? null : new RegulatoryScopeCode($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof RegulatoryScopeCode ? $value->value : (string) $value;
    }
}
