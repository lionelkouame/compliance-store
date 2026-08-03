<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Location of a document's encrypted content in the storage backend
 * (e.g. the object key in the MinIO/S3 bucket).
 */
final readonly class StorageKey
{
    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('A storage key cannot be empty.');
        }
    }

    public static function forDocument(DocumentId $id): self
    {
        return new self(\sprintf('documents/%s', $id->value));
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
