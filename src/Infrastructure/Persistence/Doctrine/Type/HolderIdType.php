<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Holder\HolderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class HolderIdType extends StringType
{
    public const NAME = 'holder_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HolderId
    {
        return \is_string($value) ? HolderId::fromString($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof HolderId ? $value->value : (\is_string($value) ? $value : null);
    }
}
