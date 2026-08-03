<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Result of CipherGatewayInterface::encrypt(): the encrypted content plus
 * the wrapped Data Key needed to decrypt it back later.
 */
final readonly class EncryptionEnvelope
{
    public function __construct(
        public EncryptedPayload $payload,
        public WrappedDataKey $wrappedDataKey,
    ) {}
}
