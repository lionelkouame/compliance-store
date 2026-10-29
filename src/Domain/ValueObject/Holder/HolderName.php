<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Holder;

use App\Domain\ValueObject\TextValueObjectTrait;

final readonly class HolderName
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($this->value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException('A holder name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('A holder name cannot exceed 255 characters.');
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
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Storage space name cannot be empty.');
        }

        return new self($value);
    }
}
