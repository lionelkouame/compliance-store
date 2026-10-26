<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\IdentityDocumentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class IdentityDocumentIdType extends StringType
{
    public const NAME = 'identity_document_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IdentityDocumentId
    {
        if (null === $value || $value instanceof IdentityDocumentId) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', IdentityDocumentId::class]);
        }

        return IdentityDocumentId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof IdentityDocumentId) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::class, ['null', 'string', IdentityDocumentId::class]);
    }
}
