<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final readonly class RegulatoryScopeCode
{
    private const UPPERCASE_SNAKE_CASE_PATTERN = '/^[A-Z][A-Z0-9_]*$/';

    public static function isValid(string $value): bool
    {
        return 1 === preg_match(self::UPPERCASE_SNAKE_CASE_PATTERN, $value);
    }

    public function __construct(
        public string $value,
    ) {
        if (!self::isValid($this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The code "%s" is invalid: expected format is UPPERCASE_SNAKE_CASE (e.g. KYC_INDIVIDUAL).',
                $this->value,
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
