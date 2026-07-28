<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class RegulatoryScopeAlreadyExistsException extends \DomainException
{
    public static function forCode(string $code): self
    {
        return new self(\sprintf('The regulatory scope "%s" already exists.', $code));
    }
}
