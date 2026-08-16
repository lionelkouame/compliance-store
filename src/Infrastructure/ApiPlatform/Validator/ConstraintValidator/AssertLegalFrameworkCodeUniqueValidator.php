<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Validator\ConstraintValidator;

use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertLegalFrameworkCodeUnique;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AssertLegalFrameworkCodeUniqueValidator extends ConstraintValidator
{
    public function __construct(
        private readonly LegalFrameworkRepositoryInterface $legalFrameworks,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AssertLegalFrameworkCodeUnique) {
            throw new UnexpectedTypeException($constraint, AssertLegalFrameworkCodeUnique::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            return;
        }

        try {
            $code = new LegalFrameworkCode($value);
        } catch (\InvalidArgumentException) {
            // Format constraint (Regex) will handle invalid value
            return;
        }

        if ($this->legalFrameworks->existsByCode($code)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
