<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Generic\BirthDate;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Maps the {@see BirthDate} value object onto a native SQL DATE column.
 *
 * The time component is irrelevant for a birth date, so only the calendar
 * day is persisted. Extends the base {@see Type} (rather than DateImmutableType)
 * so the PHP-side return type can be the BirthDate value object.
 */
final class BirthDateType extends Type
{
    public const NAME = 'birth_date';

    private const FORMAT = 'Y-m-d';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BirthDate
    {
        if (null === $value || $value instanceof BirthDate) {
            return $value;
        }

        if ($value instanceof \DateTimeImmutable) {
            return new BirthDate($value);
        }

        if (!\is_string($value)) {
            throw new \UnexpectedValueException(\sprintf(
                'Could not convert database value of type "%s" to a %s.',
                \get_debug_type($value),
                BirthDate::class,
            ));
        }

        $date = \DateTimeImmutable::createFromFormat('!' . self::FORMAT, $value);

        if (false === $date) {
            throw new \UnexpectedValueException(\sprintf(
                'Could not convert database value "%s" to a %s.',
                $value,
                BirthDate::class,
            ));
        }

        return new BirthDate($date);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof BirthDate) {
            return $value->value->format(self::FORMAT);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(self::FORMAT);
        }

        if (\is_string($value)) {
            return $value;
        }

        throw new \UnexpectedValueException(\sprintf(
            'Could not convert PHP value of type "%s" to a %s database value.',
            \get_debug_type($value),
            BirthDate::class,
        ));
    }
}
