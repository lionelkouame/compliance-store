<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class OwnerId
{
    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('An owner ID cannot be empty.');
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
