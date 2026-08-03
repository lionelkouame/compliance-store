<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\DocumentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class DocumentIdType extends StringType
{
    public const NAME = 'document_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DocumentId
    {
        return null === $value ? null : DocumentId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof DocumentId ? $value->value : (string) $value;
    }
}
