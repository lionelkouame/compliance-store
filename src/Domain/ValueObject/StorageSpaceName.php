<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class StorageSpaceName
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($this->value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException('A storage space name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('A storage space name cannot exceed 255 characters.');
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
