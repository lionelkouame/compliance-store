<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * A legal/regulatory framework code, both the identity of a LegalFramework
 * registry entry and the value referenced by Jurisdiction::applicableFrameworks
 * (e.g. FRAMEWORK-GDPR, FRAMEWORK-EIDAS2, FRAMEWORK-COMMERCIAL-CODE-FR).
 */
final readonly class LegalFrameworkCode
{
    private const PATTERN = '/^FRAMEWORK-[A-Z0-9_-]+$/D';
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
                'The legal framework code "%s" is invalid: expected format is FRAMEWORK-XXX (e.g. FRAMEWORK-GDPR), max %d characters.',
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
