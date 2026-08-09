<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Encrypted bytes of a document's content (nonce embedded by the
 * CipherGatewayInterface implementation). Only ever handed to
 * StorageGatewayInterface: never persisted in Postgres (Zero Trust Storage,
 * ADR 0002).
 */
final readonly class EncryptedPayload
{
    public function __construct(
        public string $ciphertext,
    ) {
        if ('' === $this->ciphertext) {
            throw new \InvalidArgumentException('An encrypted payload ciphertext cannot be empty.');
        }
    }
}
