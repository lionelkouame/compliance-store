<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Validator\ConstraintValidator;

use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertJurisdictionCodeUnique;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AssertJurisdictionCodeUniqueValidator extends ConstraintValidator
{
    public function __construct(
        private readonly JurisdictionRepositoryInterface $jurisdictions,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AssertJurisdictionCodeUnique) {
            throw new UnexpectedTypeException($constraint, AssertJurisdictionCodeUnique::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            return;
        }

        try {
            $code = new JurisdictionCode($value);
        } catch (\InvalidArgumentException) {
            // Format constraint (Regex) will handle invalid value
            return;
        }

        if ($this->jurisdictions->existsByCode($code)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
