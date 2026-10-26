<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway;

use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\ValueObject\EncryptedPayload;
use App\Domain\ValueObject\EncryptionEnvelope;
use App\Domain\ValueObject\WrappedDataKey;

/**
 * Envelope encryption (ADR 0002) using Libsodium secretbox: each document is
 * encrypted with a unique, random Data Key, itself encrypted by the Master
 * Key. Nonces are prepended to their respective ciphertext.
 */
final readonly class SodiumCipherGateway implements CipherGatewayInterface
{
    public function __construct(
        private string $masterKey,
    ) {
        if (\SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== \strlen($this->masterKey)) {
            throw new \InvalidArgumentException(\sprintf(
                'The document master key must be %d bytes long.',
                \SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            ));
        }
    }

    public function encrypt(string $plaintext): EncryptionEnvelope
    {
        $dataKey = sodium_crypto_secretbox_keygen();
        $fileNonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = $fileNonce.sodium_crypto_secretbox($plaintext, $fileNonce, $dataKey);

        $keyNonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encryptedDataKey = $keyNonce.sodium_crypto_secretbox($dataKey, $keyNonce, $this->masterKey);

        sodium_memzero($dataKey);

        return new EncryptionEnvelope(
            payload: new EncryptedPayload($ciphertext),
            wrappedDataKey: new WrappedDataKey($encryptedDataKey),
        );
    }

    /**
     * @throws \RuntimeException when the data is truncated, corrupted or was not produced with this master key
     */
    public function decrypt(EncryptedPayload $payload, WrappedDataKey $wrappedDataKey): string
    {
        [$keyNonce, $encryptedDataKey] = $this->splitNonce($wrappedDataKey->encryptedDataKey, 'wrapped data key');
        [$fileNonce, $ciphertext] = $this->splitNonce($payload->ciphertext, 'encrypted payload');

        try {
            $dataKey = sodium_crypto_secretbox_open($encryptedDataKey, $keyNonce, $this->masterKey);
        } catch (\SodiumException $e) {
            throw new \RuntimeException('Unable to unwrap the document data key: the data is malformed.', previous: $e);
        }

        if (false === $dataKey) {
            throw new \RuntimeException('Unable to unwrap the document data key: the master key does not match or the data is corrupted.');
        }

        try {
            $plaintext = sodium_crypto_secretbox_open($ciphertext, $fileNonce, $dataKey);
        } catch (\SodiumException $e) {
            throw new \RuntimeException('Unable to decrypt the document: the data is malformed.', previous: $e);
        } finally {
            sodium_memzero($dataKey);
        }

        if (false === $plaintext) {
            throw new \RuntimeException('Unable to decrypt the document: the data key does not match or the data is corrupted.');
        }

        return $plaintext;
    }

    /**
     * Splits "nonce . secretbox" blobs, refusing anything too short to hold a
     * nonce and an authentication tag (sodium would throw instead of returning false).
     *
     * @return array{0: string, 1: string} [nonce, remainder]
     *
     * @throws \RuntimeException when the blob is shorter than nonce + MAC
     */
    private function splitNonce(string $value, string $what): array
    {
        $minimum = \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + \SODIUM_CRYPTO_SECRETBOX_MACBYTES;

        if (\strlen($value) < $minimum) {
            throw new \RuntimeException(\sprintf('The %s is truncated: at least %d bytes are expected.', $what, $minimum));
        }

        return [
            substr($value, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($value, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
        ];
    }
}
