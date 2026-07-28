<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\RegulatoryScopeLabel;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class RegulatoryScopeLabelType extends StringType
{
    public const NAME = 'regulatory_scope_label';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RegulatoryScopeLabel
    {
        return null === $value ? null : new RegulatoryScopeLabel($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof RegulatoryScopeLabel ? $value->value : (string) $value;
    }
}
