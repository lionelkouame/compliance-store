<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($this->value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException('The email cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('The mail cannot exceed 255 characters.');
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
