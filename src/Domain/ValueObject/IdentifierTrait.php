<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Port\Service\IdValidatorInterface;

trait IdentifierTrait
{
    /**
     * @throws InvalidValueException when the identifier is malformed
     */
    private function __construct(
        private readonly string $value,
        ?IdValidatorInterface $validator = null,
    ) {
        if (null !== $validator && !$validator->isValid($this->value)) {
            throw new InvalidValueException(\sprintf(
                'The ID "%s" is not valid.',
                $this->value,
            ));
        }
    }

    /**
     * @throws InvalidValueException when the identifier is malformed
     */
    public static function make(string $value, ?IdValidatorInterface $validator = null): self
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
    public function value(): string
    {
        return $this->value;
    }
}
