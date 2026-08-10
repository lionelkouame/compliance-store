<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidJurisdictionException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('The jurisdiction "%s" is inactive or unknown.', $code));
    }
}
