<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\IdentityDocumentNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class IdentityDocumentNumberType extends StringType
{
    public const NAME = 'identity_document_number';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IdentityDocumentNumber
    {
        if (null === $value || $value instanceof IdentityDocumentNumber) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', IdentityDocumentNumber::class]);
        }

        return new IdentityDocumentNumber($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof IdentityDocumentNumber) {
            return $value->value;
        }

        if (\is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, self::class, ['null', 'string', IdentityDocumentNumber::class]);
    }
}
