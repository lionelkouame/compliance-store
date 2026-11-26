<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Shared\Generic;

use App\Domain\Exception\InvalidValueException;

/**
 * Encrypted bytes of a document's content (nonce embedded by the
 * CipherGatewayInterface implementation). Only ever handed to
 * StorageGatewayInterface: never persisted in Postgres (Zero Trust Storage,
 * ADR 0002).
 */
final readonly class EncryptedPayload
{
    /**
     * @throws InvalidValueException when the ciphertext is empty
     */
    private function __construct(
        private string $value,
    ) {
        if ('' === $this->value) {
            throw new InvalidValueException('An encrypted payload ciphertext cannot be empty.');
        }
    }

    /**
     * @throws InvalidValueException when the ciphertext is empty
     */
    public static function make(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
