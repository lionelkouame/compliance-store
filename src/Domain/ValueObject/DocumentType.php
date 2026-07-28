<?php

namespace App\Domain\ValueObject;

final readonly class DocumentType
{
    public function __construct(
        public string $value,
    ) {
        if ('' === trim($this->value)) {
            throw new \InvalidArgumentException('Un type de document ne peut pas être vide.');
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
}
