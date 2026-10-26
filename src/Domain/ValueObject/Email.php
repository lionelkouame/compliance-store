<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Email address of a holder. It identifies the holder: two holders cannot share it.
 */
final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The email cannot be empty.');
        }

        if (mb_strlen($this->value) > 255) {
            throw new \InvalidArgumentException('The email cannot exceed 255 characters.');
        }

        if (false === filter_var($this->value, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(\sprintf('The email "%s" is not a valid address.', $this->value));
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
}
