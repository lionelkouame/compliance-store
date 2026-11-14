<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\IdentityDocument;

use App\Domain\Exception\InvalidValueException;

/**
 * The expiry date of an IdentityDocument: a calendar day (UTC, midnight).
 *
 * A past date is a valid ExpiryDate (a stored document eventually expires):
 * refusing to register an already expired document is a rule of the
 * IdentityDocument aggregate, not of this value object.
 */
final readonly class ExpiryDate
{
    private const string FORMAT = 'Y-m-d';

    public \DateTimeImmutable $value;

    public function __construct(\DateTimeImmutable $value)
    {
        // Only the calendar day matters: normalise to midnight UTC.
        $this->value = new \DateTimeImmutable($value->format(self::FORMAT), new \DateTimeZone('UTC'));
    }

    /**
     * @throws InvalidValueException when $value is not an existing Y-m-d date
     */
    public static function make(string $value): self
    {
        $date = \DateTimeImmutable::createFromFormat('!'.self::FORMAT, $value, new \DateTimeZone('UTC'));

        // createFromFormat() silently rolls impossible dates over (2030-02-30 -> 2030-03-02).
        if (false === $date || $date->format(self::FORMAT) !== $value) {
            throw new InvalidValueException(\sprintf('The expiry date "%s" must be a valid Y-m-d date.', $value));
        }

        return new self($date);
    }

    /**
     * A document is valid through its whole expiry day.
     */
    public function isExpiredOn(\DateTimeImmutable $day): bool
    {
        return $this->value->format(self::FORMAT) < $day->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMAT);
    }

    public function equals(self $other): bool
    {
        return (string) $this === (string) $other;
    }

    public function __toString(): string
    {
        return $this->value->format(self::FORMAT);
    }
}
