<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * A legal/regulatory framework applicable to a jurisdiction (e.g. GDPR, EIDAS_2).
 */
final readonly class LegalFrameworkCode
{
    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('A legal framework code cannot be empty.');
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
