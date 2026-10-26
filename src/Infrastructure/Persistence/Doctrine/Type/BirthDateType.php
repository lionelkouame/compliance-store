<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\BirthDate;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

/**
 * Stores a BirthDate as a plain SQL DATE (no time, no timezone).
 *
 * Extends Type directly rather than DateImmutableType: the parent fixes the
 * return type of convertToPHPValue() to ?DateTimeImmutable, which would
 * forbid returning the value object.
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

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', 'string', BirthDate::class]);
        }

        // "!" resets the time to midnight so only the calendar day is kept.
        $date = \DateTimeImmutable::createFromFormat('!'.self::FORMAT, $value, new \DateTimeZone('UTC'));

        if (false === $date) {
            throw InvalidFormat::new($value, self::class, self::FORMAT);
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

        throw InvalidType::new($value, self::class, ['null', BirthDate::class, \DateTimeInterface::class]);
    }
}
