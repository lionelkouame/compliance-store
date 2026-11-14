<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Port\Service\IdValidatorInterface;

/**
 * Per-document Data Key, encrypted under the Master Key (envelope
 * encryption, ADR 0002; nonce embedded by the CipherGatewayInterface
 * implementation). Small enough to be persisted alongside the document's
 * metadata; required to decrypt its EncryptedPayload later.
 */
final readonly class WrappedDataKey
{
    private function __construct(
        private string $value,
    ) {
        if ('' === $this->value) {
            throw new InvalidValueException('A wrapped data key cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public function value(): string
    {
        return $this->value;
    }
    public static function make(string $value): self
    {
        return new self($value);
    }
}
