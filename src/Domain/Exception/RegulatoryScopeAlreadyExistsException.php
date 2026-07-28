<?php

namespace App\Domain\Exception;

final class RegulatoryScopeAlreadyExistsException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('Le périmètre réglementaire "%s" existe déjà.', $code));
    }
}
