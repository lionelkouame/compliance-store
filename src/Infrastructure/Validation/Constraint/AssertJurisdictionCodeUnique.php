<?php

declare(strict_types=1);

namespace App\Infrastructure\Validation\Constraint;

use App\Infrastructure\Validation\ConstraintValidator\AssertJurisdictionCodeUniqueValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class AssertJurisdictionCodeUnique extends Constraint
{
    public string $message = 'The jurisdiction code "{{ value }}" is already in use.';

    public function validatedBy(): string
    {
        return AssertJurisdictionCodeUniqueValidator::class;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
