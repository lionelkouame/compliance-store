<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\NaturalPerson\LastName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class LastNameType extends StringType
{
    public const NAME = 'last_name';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?LastName
    {
        return \is_string($value) ? new LastName($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof LastName ? $value->value : (\is_string($value) ? $value : null);
    }
}
