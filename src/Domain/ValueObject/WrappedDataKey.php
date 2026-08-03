<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Per-document Data Key, encrypted under the Master Key (envelope
 * encryption, ADR 0002; nonce embedded by the CipherGatewayInterface
 * implementation). Small enough to be persisted alongside the document's
 * metadata; required to decrypt its EncryptedPayload later.
 */
final readonly class WrappedDataKey
{
    public function __construct(
        public string $encryptedDataKey,
    ) {
        if ('' === $this->encryptedDataKey) {
            throw new \InvalidArgumentException('A wrapped data key cannot be empty.');
        }
    }
}
