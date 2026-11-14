<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\IdentityDocument;

use App\Domain\Exception\InvalidValueException;

/**
 * Number printed on an identity document (e.g. "18AB12345"), unique for a
 * given document type and issuing country.
 */
final readonly class IdentityDocumentNumber
{
    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match('/^[A-Z0-9]{1,20}$/', $this->value)) {
            throw new InvalidValueException('An identity document number must be 1 to 20 uppercase alphanumeric characters.');
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
