<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

use App\Domain\Exception\InvalidValueException;

final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($this->value);

        if ('' === $trimmed) {
            throw new InvalidValueException('The email cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidValueException('The email cannot exceed 255 characters.');
        }

        // Validated here and not only on the HTTP DTO: the Domain must hold its
        // invariant whatever the entry channel (ADR 0005, double lock).
        if (false === filter_var($this->value, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidValueException('The email is not a valid email address.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function make(string $value): self
    {
        return new self($value);
    }
}
