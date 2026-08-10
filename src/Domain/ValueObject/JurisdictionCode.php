<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class JurisdictionCode
{
    private const PATTERN = '/^JUR-[A-Z0-9_-]+$/';
    private const MAX_LENGTH = 64;

    public static function isValid(string $value): bool
    {
        return 1 === preg_match(self::PATTERN, $value) && \strlen($value) <= self::MAX_LENGTH;
    }

    public function __construct(
        public string $value,
    ) {
        if (!self::isValid($this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The code "%s" is invalid: expected format is JUR-XXX (e.g. JUR-EU-FRA), max %d characters.',
                $this->value,
                self::MAX_LENGTH,
            ));
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
