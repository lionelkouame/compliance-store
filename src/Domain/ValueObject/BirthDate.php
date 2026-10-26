<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * The birth date of a Holder (data subject).
 *
 * Invariant: a birth date is always strictly in the past. The time component
 * is irrelevant here, so only the calendar day (UTC) is compared.
 */
final readonly class BirthDate
{
    public \DateTimeImmutable $value;

    public function __construct(\DateTimeImmutable $value)
    {
        // Normalise to UTC first so the calendar day never depends on the
        // timezone the caller happened to use.
        $value = $value->setTimezone(new \DateTimeZone('UTC'));
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));

        if ($value->format('Y-m-d') >= $today->format('Y-m-d')) {
            throw new \InvalidArgumentException(\sprintf(
                'The birth date "%s" must be in the past.',
                $value->format('Y-m-d'),
            ));
        }

        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $this->value->format('Y-m-d') === $other->value->format('Y-m-d');
    }

    /**
     * @param string $value a calendar day in Y-m-d format
     *
     * @throws \InvalidArgumentException when the string is not a valid Y-m-d date
     */
    public static function fromString(string $value): self
    {
        // "!" resets the unspecified fields (time) to zero so only the day matters.
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));

        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException(\sprintf(
                'The birth date "%s" is not a valid date in Y-m-d format.',
                $value,
            ));
        }

        return new self($date);
    }

    public function __toString(): string
    {
        return $this->value->format('Y-m-d');
    }
}
