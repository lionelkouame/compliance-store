<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Human-readable, unique code identifying a storage space (e.g. "EU-WEST-1").
 */
final readonly class StorageSpaceCode
{
    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match('/^[A-Z0-9](?:[A-Z0-9_-]{0,62}[A-Z0-9])?$/', $this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The storage space code "%s" must be 1 to 64 uppercase alphanumeric characters, dashes or underscores, and cannot start or end with a separator.',
                $this->value,
            ));
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
