<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\ApplicableFrameworks;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\JsonType;

final class ApplicableFrameworksType extends JsonType
{
    public const NAME = 'applicable_frameworks';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ApplicableFrameworks
    {
        $decoded = parent::convertToPHPValue($value, $platform);

        if (null === $decoded) {
            return ApplicableFrameworks::fromStrings();
        }

        if (!\is_array($decoded)) {
            throw InvalidType::new($decoded, self::NAME, ['null', 'string[]']);
        }

        foreach ($decoded as $item) {
            if (!\is_string($item)) {
                throw InvalidType::new($decoded, self::NAME, ['null', 'string[]']);
            }
        }

        /** @var string[] $decoded */
        return ApplicableFrameworks::fromStrings(...$decoded);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof ApplicableFrameworks) {
            $values = [];
            foreach ($value as $framework) {
                $values[] = $framework->value;
            }

            $value = $values;
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
