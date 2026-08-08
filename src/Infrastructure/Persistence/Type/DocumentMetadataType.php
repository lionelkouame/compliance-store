<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Type;

use App\Domain\ValueObject\DocumentMetadata;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

final class DocumentMetadataType extends JsonType
{
    public const NAME = 'document_metadata';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DocumentMetadata
    {
        $decoded = parent::convertToPHPValue($value, $platform);

        if (!\is_array($decoded) || !isset($decoded['country'], $decoded['retentionYears']) || !\is_string($decoded['country']) || !\is_int($decoded['retentionYears'])) {
            return null;
        }

        return new DocumentMetadata($decoded['country'], $decoded['retentionYears']);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof DocumentMetadata) {
            $value = [
                'country' => $value->country,
                'retentionYears' => $value->retentionYears,
            ];
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
