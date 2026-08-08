<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\OwnerId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class OwnerIdType extends StringType
{
    public const NAME = 'owner_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OwnerId
    {
        return \is_string($value) ? new OwnerId($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof OwnerId ? $value->value : (\is_string($value) ? $value : null);
    }
}
