<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * SHA-256 digest of a document's plaintext content, used to verify its
 * integrity once decrypted back.
 */
final readonly class FileHash
{
    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match('/^[a-f0-9]{64}$/', $this->value)) {
            throw new \InvalidArgumentException('A file hash must be a 64-character lowercase hexadecimal SHA-256 digest.');
        }
    }

    public static function fromPlaintext(string $content): self
    {
        return new self(hash('sha256', $content));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
