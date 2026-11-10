<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\NaturalPerson\FirstName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class FirstNameType extends StringType
{
    public const NAME = 'first_name';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?FirstName
    {
        return \is_string($value) ? new FirstName($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof FirstName ? $value->value : (\is_string($value) ? $value : null);
    }
}
