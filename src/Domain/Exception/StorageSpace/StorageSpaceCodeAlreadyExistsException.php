<?php

declare(strict_types=1);

namespace App\Domain\Exception\StorageSpace;

use App\Domain\Exception\AlreadyExistException;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;

final class StorageSpaceCodeAlreadyExistsException extends AlreadyExistException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * A storage space code is a technical label, not personal data: it can be
     * echoed in the message (ADR 0013 §6).
     */
    public static function withCode(StorageSpaceCode $code): self
    {
        return new self(\sprintf('A storage space with code "%s" already exists.', $code->value));
    }
}
