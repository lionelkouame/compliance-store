<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Document;

use App\Domain\Port\Service\IdValidatorInterface;

/**
 * SHA-256 digest of a document's plaintext content, used to verify its
 * integrity once decrypted back.
 */
final readonly class FileHash
{
    private function __construct(
        private string $value,
    ) {
        if (1 !== preg_match('/^[a-f0-9]{64}$/', $this->value)) {
            throw new \InvalidArgumentException('A file hash must be a 64-character lowercase hexadecimal SHA-256 digest.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
    public function value(): string
    {
        return $this->value;
    }

    public static function make(string $content): self
    {
        return new self(hash('sha256', $content));
    }

    /**
     * Rebuilds a FileHash from an already-computed digest (e.g. read back
     * from persistence), without hashing it again.
     */
    public static function fromDigest(string $digest): self
    {
        return new self($digest);
    }

}
