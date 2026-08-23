<?php

declare(strict_types=1);

namespace App\Infrastructure\Validation\Constraint;

use App\Infrastructure\Validation\ConstraintValidator\AssertRegulatoryScopeCodeUniqueValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class AssertRegulatoryScopeCodeUnique extends Constraint
{
    public string $message = 'The regulatory scope code "{{ value }}" is already in use.';

    public function validatedBy(): string
    {
        return AssertRegulatoryScopeCodeUniqueValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
