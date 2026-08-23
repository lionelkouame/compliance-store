<?php

declare(strict_types=1);

namespace App\Infrastructure\Validation\ConstraintValidator;

use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Infrastructure\Validation\Constraint\AssertRegulatoryScopeCodeUnique;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AssertRegulatoryScopeCodeUniqueValidator extends ConstraintValidator
{
    public function __construct(
        private readonly RegulatoryScopeRepositoryInterface $regulatoryScopes,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AssertRegulatoryScopeCodeUnique) {
            throw new UnexpectedTypeException($constraint, AssertRegulatoryScopeCodeUnique::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            return;
        }

        try {
            $code = new RegulatoryScopeCode($value);
        } catch (\InvalidArgumentException) {
            // Format constraint (Regex) will handle invalid value
            return;
        }

        if ($this->regulatoryScopes->existsByCode($code)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
