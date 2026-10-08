<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\IdentityDocumentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class IdentityDocumentIdType extends StringType
{
    public const NAME = 'identity_document_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IdentityDocumentId
    {
        return \is_string($value) ? IdentityDocumentId::fromString($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof IdentityDocumentId ? $value->value : (\is_string($value) ? $value : null);
    }
}
