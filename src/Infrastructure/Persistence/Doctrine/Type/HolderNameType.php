<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Holder\HolderName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class HolderNameType extends StringType
{
    public const NAME = 'holder_name';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HolderName
    {
        return \is_string($value) ?  new HolderName($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof HolderName ? $value->value : (\is_string($value) ? $value : null);
    }
}
