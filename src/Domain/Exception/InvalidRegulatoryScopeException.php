<?php

namespace App\Domain\Exception;

final class InvalidRegulatoryScopeException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('Le périmètre réglementaire "%s" est inactif ou inconnu.', $code));
    }
}
