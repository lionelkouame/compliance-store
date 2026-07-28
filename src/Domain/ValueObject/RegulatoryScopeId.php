<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Port\Service\IdValidatorInterface;

final readonly class RegulatoryScopeId
{
    public function __construct(
        public string $value,
        ?IdValidatorInterface $validator = null,
    ) {
        if (null !== $validator && !$validator->isValid($this->value)) {
            throw new \InvalidArgumentException(\sprintf(
                'The ID "%s" is not valid.',
                $this->value,
            ));
        }
    }

    public static function fromString(string $value, ?IdValidatorInterface $validator = null): self
    {
        return new self($value, $validator);
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
