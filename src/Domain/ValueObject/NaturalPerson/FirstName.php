<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\NaturalPerson;

use App\Domain\Exception\InvalidValueException;

final readonly class FirstName
{
    /**
     * @throws InvalidValueException when the first name is empty or too long
     */
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($this->value);

        if ('' === $trimmed) {
            throw new InvalidValueException('A first name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidValueException('A first name cannot exceed 255 characters.');
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

    /**
     * @throws InvalidValueException when the first name is empty or too long
     */
    public static function make(string $value): self
    {
        return new self($value);
    }
}
