<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * ISO 3166-1 alpha-3 country code (e.g. FRA, DEU, USA, CHE).
 *
 * Null at the Jurisdiction level means "whole region" (see JurisdictionRegion).
 */
final readonly class JurisdictionCountry
{
    private const PATTERN = '/^[A-Z]{3}$/';

    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match(self::PATTERN, $this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The country "%s" is invalid: expected ISO 3166-1 alpha-3 code (e.g. FRA, DEU, USA).',
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
