<?php

namespace App\Domain\Exception;

final class InvalidRegulatoryScopeException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('The regulatory scope "%s" is inactive or unknown.', $code));
    }
}
