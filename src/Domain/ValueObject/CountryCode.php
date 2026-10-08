<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * ISO 3166-1 alpha-3 country code (e.g. "FRA"), as printed on identity
 * documents (ICAO 9303). Only the format is checked here: the Domain has no
 * dependency on a country registry.
 */
final readonly class CountryCode
{
    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match('/^[A-Z]{3}$/', $this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The country code "%s" must be an ISO 3166-1 alpha-3 code (3 uppercase letters).',
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
