<?php

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\AllowedDocumentTypes;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

final class AllowedDocumentTypesType extends JsonType
{
    public const NAME = 'allowed_document_types';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): AllowedDocumentTypes
    {
        $decoded = parent::convertToPHPValue($value, $platform);

        return AllowedDocumentTypes::fromStrings(...($decoded ?? []));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof AllowedDocumentTypes) {
            $values = [];
            foreach ($value as $documentType) {
                $values[] = $documentType->value;
            }

            $value = $values;
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
