<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\AllowedDocumentTypes;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\JsonType;

final class AllowedDocumentTypesType extends JsonType
{
    public const NAME = 'allowed_document_types';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): AllowedDocumentTypes
    {
        $decoded = parent::convertToPHPValue($value, $platform);

        if (null === $decoded) {
            return AllowedDocumentTypes::fromStrings();
        }

        if (!\is_array($decoded)) {
            throw InvalidType::new($decoded, self::NAME, ['null', 'string[]']);
        }

        foreach ($decoded as $item) {
            if (!\is_string($item)) {
                throw InvalidType::new($decoded, self::NAME, ['null', 'string[]']);
            }
        }

        /** @var string[] $decoded */
        return AllowedDocumentTypes::fromStrings(...$decoded);
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
