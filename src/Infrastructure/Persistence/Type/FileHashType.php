<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\FileHash;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class FileHashType extends StringType
{
    public const NAME = 'file_hash';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?FileHash
    {
        return \is_string($value) ? new FileHash($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof FileHash ? $value->value : (\is_string($value) ? $value : null);
    }
}
