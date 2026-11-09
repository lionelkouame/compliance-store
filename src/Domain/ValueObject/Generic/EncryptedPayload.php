<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Generic;

/**
 * Encrypted bytes of a document's content (nonce embedded by the
 * CipherGatewayInterface implementation). Only ever handed to
 * StorageGatewayInterface: never persisted in Postgres (Zero Trust Storage,
 * ADR 0002).
 */
final readonly class EncryptedPayload
{
    private function __construct(
        private string $value,
    ) {
        if ('' === $this->value) {
            throw new \InvalidArgumentException('An encrypted payload ciphertext cannot be empty.');
        }
    }

    public static function make(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
