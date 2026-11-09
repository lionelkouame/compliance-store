<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Document\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class EmailType extends StringType
{
    public const NAME = 'email';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Email
    {
        return \is_string($value) ?  new Email($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof Email ? $value->value : (\is_string($value) ? $value : null);
    }
}
