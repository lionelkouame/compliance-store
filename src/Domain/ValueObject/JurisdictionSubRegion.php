<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Optional state/sub-region code (e.g. CA for California). Null when the
 * jurisdiction is not scoped below country level.
 */
final readonly class JurisdictionSubRegion
{
    private const MAX_LENGTH = 64;

    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('The jurisdiction sub-region cannot be empty.');
        }

        if (\strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The jurisdiction sub-region cannot exceed %d characters.',
                self::MAX_LENGTH,
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
