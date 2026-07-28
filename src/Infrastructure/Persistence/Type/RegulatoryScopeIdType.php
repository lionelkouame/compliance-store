<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\RegulatoryScopeId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class RegulatoryScopeIdType extends StringType
{
    public const NAME = 'regulatory_scope_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RegulatoryScopeId
    {
        return null === $value ? null : RegulatoryScopeId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof RegulatoryScopeId ? $value->value : (string) $value;
    }
}
