<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Display name of a StorageSpace. Surrounding whitespace is not significant:
 * the value is stored trimmed so that equality and the 255-character limit
 * apply to what is actually persisted.
 */
final readonly class StorageSpaceName
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException('A storage space name cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('A storage space name cannot exceed 255 characters.');
        }

        $this->value = $trimmed;
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
