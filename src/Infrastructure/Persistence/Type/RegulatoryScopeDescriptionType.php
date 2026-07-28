<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\RegulatoryScopeDescription;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class RegulatoryScopeDescriptionType extends TextType
{
    public const NAME = 'regulatory_scope_description';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): RegulatoryScopeDescription
    {
        $value = parent::convertToPHPValue($value, $platform);

        return new RegulatoryScopeDescription($value ?? '');
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof RegulatoryScopeDescription) {
            return $value->value;
        }

        return null === $value ? null : (string) $value;
    }
}
