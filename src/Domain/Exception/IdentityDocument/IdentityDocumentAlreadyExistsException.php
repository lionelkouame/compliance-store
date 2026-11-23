<?php

declare(strict_types=1);

namespace App\Domain\Exception\IdentityDocument;

use App\Domain\Exception\AlreadyExistException;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\CountryCode;

final class IdentityDocumentAlreadyExistsException extends AlreadyExistException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * The document number is personal data: it is deliberately left out of the
     * message, which is sent to the client and may be logged (ADR 0013 §6).
     */
    public static function withType(IdentityDocumentType $type, CountryCode $country): self
    {
        return new self(\sprintf(
            'A %s issued by %s with this number already exists.',
            $type->value,
            $country->value,
        ));
    }
}
