<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class JurisdictionAlreadyExistsException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('The jurisdiction "%s" already exists.', $code));
    }
}
