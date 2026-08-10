<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Dynamic macro-region code (e.g. EU, NA, LATAM, APAC, MENA, NORDICS, GLOBAL).
 *
 * Not a hardcoded enum: new regions can be introduced without redeployment,
 * only the uppercase-letters format is enforced.
 */
final readonly class JurisdictionRegion
{
    private const PATTERN = '/^[A-Z]{2,}$/';
    private const MAX_LENGTH = 32;

    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match(self::PATTERN, $this->value) || \strlen($this->value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(\sprintf(
                'The region "%s" is invalid: expected uppercase letters only (e.g. EU, NA, GLOBAL), max %d characters.',
                $this->value,
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
