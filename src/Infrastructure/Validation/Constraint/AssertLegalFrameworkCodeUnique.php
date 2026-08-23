<?php

declare(strict_types=1);

namespace App\Infrastructure\Validation\Constraint;

use App\Infrastructure\Validation\ConstraintValidator\AssertLegalFrameworkCodeUniqueValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class AssertLegalFrameworkCodeUnique extends Constraint
{
    public string $message = 'The legal framework code "{{ value }}" is already in use.';

    public function validatedBy(): string
    {
        return AssertLegalFrameworkCodeUniqueValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
