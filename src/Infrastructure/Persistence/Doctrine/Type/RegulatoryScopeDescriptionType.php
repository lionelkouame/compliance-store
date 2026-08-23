<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\RegulatoryScopeDescription;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\TextType;

final class RegulatoryScopeDescriptionType extends TextType
{
    public const NAME = 'regulatory_scope_description';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): RegulatoryScopeDescription
    {
        $value = parent::convertToPHPValue($value, $platform);

        if (null !== $value && !\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return new RegulatoryScopeDescription($value ?? '');
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof RegulatoryScopeDescription) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', RegulatoryScopeDescription::class]);
    }
}
