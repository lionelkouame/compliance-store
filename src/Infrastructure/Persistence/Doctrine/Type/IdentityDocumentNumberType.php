<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\IdentityDocumentNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class IdentityDocumentNumberType extends StringType
{
    public const NAME = 'identity_document_number';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IdentityDocumentNumber
    {
        return \is_string($value) ? new IdentityDocumentNumber($value) : null;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof IdentityDocumentNumber ? $value->value : (\is_string($value) ? $value : null);
    }
}
