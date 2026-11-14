<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\IdentityDocument\ExpiryDate;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Maps the {@see ExpiryDate} value object onto a native SQL DATE column.
 */
final class ExpiryDateType extends Type
{
    public const NAME = 'expiry_date';

    private const FORMAT = 'Y-m-d';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ExpiryDate
    {
        if (null === $value || $value instanceof ExpiryDate) {
            return $value;
        }

        if ($value instanceof \DateTimeImmutable) {
            return new ExpiryDate($value);
        }

        if (!\is_string($value)) {
            throw new \UnexpectedValueException(\sprintf(
                'Could not convert database value of type "%s" to a %s.',
                get_debug_type($value),
                ExpiryDate::class,
            ));
        }

        $date = \DateTimeImmutable::createFromFormat('!'.self::FORMAT, $value, new \DateTimeZone('UTC'));

        if (false === $date) {
            throw new \UnexpectedValueException(\sprintf(
                'Could not convert database value "%s" to a %s.',
                $value,
                ExpiryDate::class,
            ));
        }

        return new ExpiryDate($date);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof ExpiryDate) {
            return (string) $value;
        }

        throw new \UnexpectedValueException(\sprintf(
            'Could not convert PHP value of type "%s" to a %s database value.',
            get_debug_type($value),
            ExpiryDate::class,
        ));
    }
}
