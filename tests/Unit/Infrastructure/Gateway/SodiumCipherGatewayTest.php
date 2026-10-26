<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Gateway;

use App\Domain\ValueObject\EncryptedPayload;
use App\Domain\ValueObject\WrappedDataKey;
use App\Infrastructure\Gateway\SodiumCipherGateway;
use PHPUnit\Framework\TestCase;

final class SodiumCipherGatewayTest extends TestCase
{
    public function testEncryptThenDecryptRoundTripsThePlaintext(): void
    {
        $gateway = new SodiumCipherGateway(random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $envelope = $gateway->encrypt('sensitive document content');

        self::assertNotSame('sensitive document content', $envelope->payload->ciphertext);

        $plaintext = $gateway->decrypt($envelope->payload, $envelope->wrappedDataKey);

        self::assertSame('sensitive document content', $plaintext);
    }

    public function testDecryptFailsWithAWrongMasterKey(): void
    {
        $envelope = (new SodiumCipherGateway(random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES)))->encrypt('secret');

        $otherGateway = new SodiumCipherGateway(random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $this->expectException(\RuntimeException::class);
        $otherGateway->decrypt($envelope->payload, $envelope->wrappedDataKey);
    }

    public function testConstructorRejectsAMasterKeyWithTheWrongLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SodiumCipherGateway('too-short');
    }

    public function testDecryptRejectsATruncatedWrappedKeyWithARuntimeException(): void
    {
        $gateway = new SodiumCipherGateway(random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $envelope = $gateway->encrypt('secret');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wrapped data key is truncated');

        $gateway->decrypt($envelope->payload, new WrappedDataKey('short'));
    }

    public function testDecryptRejectsATruncatedPayloadWithARuntimeException(): void
    {
        $gateway = new SodiumCipherGateway(random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $envelope = $gateway->encrypt('secret');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('encrypted payload is truncated');

        $gateway->decrypt(new EncryptedPayload('short'), $envelope->wrappedDataKey);
    }

    public function testDecryptRejectsACorruptedPayloadWithARuntimeException(): void
    {
        $gateway = new SodiumCipherGateway(random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $envelope = $gateway->encrypt('secret');
        $corrupted = $envelope->payload->ciphertext;
        $corrupted[\strlen($corrupted) - 1] = \chr(\ord($corrupted[\strlen($corrupted) - 1]) ^ 0x01);

        $this->expectException(\RuntimeException::class);

        $gateway->decrypt(new EncryptedPayload($corrupted), $envelope->wrappedDataKey);
    }
}
