<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Generic;

/**
 * The birth date of a Holder (data subject).
 *
 * Invariant: a birth date is always strictly in the past. The time component
 * is irrelevant here, so only the calendar day (UTC) is compared.
 */
final readonly class BirthDate
{
    public \DateTimeImmutable $value;

    public function __construct(\DateTimeImmutable  $value)
    {
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

    public static function make(string $value): self
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (false === $date) {
            throw new \InvalidArgumentException(\sprintf('The birth date "%s" is not valid.', $value));
        }

        return new self($date);
    }

    public function __toString(): string
    {
        return $this->value->format('Y-m-d');
    }
}
