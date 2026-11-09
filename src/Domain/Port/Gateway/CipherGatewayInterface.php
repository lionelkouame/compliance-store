<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use App\Domain\ValueObject\Document\WrappedDataKey;
use App\Domain\ValueObject\Generic\EncryptedPayload;
use App\Domain\ValueObject\Generic\EncryptionEnvelope;

/**
 * Envelope encryption port (ADR 0002): no byte is sent to
 * StorageGatewayInterface without first being encrypted here.
 */
interface CipherGatewayInterface
{
    public function encrypt(string $plaintext): EncryptionEnvelope;

    public function decrypt(EncryptedPayload $payload, WrappedDataKey $wrappedDataKey): string;
}
