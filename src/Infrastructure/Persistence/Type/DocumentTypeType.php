<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\DocumentType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class DocumentTypeType extends StringType
{
    public const NAME = 'document_type';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DocumentType
    {
        return null === $value ? null : new DocumentType($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof DocumentType ? $value->value : (string) $value;
    }
}
