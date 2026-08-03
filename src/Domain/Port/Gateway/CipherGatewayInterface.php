<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use App\Domain\ValueObject\EncryptedPayload;
use App\Domain\ValueObject\EncryptionEnvelope;
use App\Domain\ValueObject\WrappedDataKey;

/**
 * Envelope encryption port (ADR 0002): no byte is sent to
 * StorageGatewayInterface without first being encrypted here.
 */
interface CipherGatewayInterface
{
    public function encrypt(string $plaintext): EncryptionEnvelope;

    public function decrypt(EncryptedPayload $payload, WrappedDataKey $wrappedDataKey): string;
}
