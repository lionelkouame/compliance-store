<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

use App\Domain\Exception\InvalidValueException;

enum IdentityDocumentType: string
{
    case Passport = 'Passport';
    case NationalCard = 'NationalCard';

    /**
     * @throws InvalidValueException when $value is not a known type
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidValueException(\sprintf(
            'The identity document type "%s" must be one of: %s.',
            $value,
            implode(', ', array_column(self::cases(), 'value')),
        ));
    }
}
