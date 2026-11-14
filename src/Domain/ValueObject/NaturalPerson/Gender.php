<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\NaturalPerson;

use App\Domain\Exception\InvalidValueException;

/**
 * Administrative gender of a NaturalPerson. Optional on creation: an
 * {@see Gender::Unspecified} value is used when the subject does not
 * provide one.
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case Unspecified = 'unspecified';

    public static function fromNullable(?string $value): self
    {
        if (null === $value || '' === trim($value)) {
            return self::Unspecified;
        }

        $gender = self::tryFrom(strtolower(trim($value)));

        if (null === $gender) {
            throw new InvalidValueException(\sprintf('The gender "%s" is not valid.', $value));
        }

        return $gender;
    }
}
