<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

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

        $rawAttributes = $decoded['attributes'] ?? [];
        $attributes = [];
        if (\is_array($rawAttributes)) {
            foreach ($rawAttributes as $k => $v) {
                if (\is_string($k)) {
                    $attributes[$k] = $v;
                }
            }
        }

        return new DocumentMetadata($decoded['country'], $decoded['retentionYears'], $attributes);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof DocumentMetadata) {
            $value = [
                'country' => $value->country,
                'retentionYears' => $value->retentionYears,
                'attributes' => $value->attributes,
            ];
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
