<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Validator\ConstraintValidator;

use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertRegulatoryScopeCodeUnique;
use Symfony\Component\Validator\Attribute\AsConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

#[AsConstraintValidator]
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

        try {
            $code = new RegulatoryScopeCode((string) $value);
        } catch (\InvalidArgumentException) {
            // Format constraint (Regex) will handle invalid value
            return;
        }

        if ($this->regulatoryScopes->existsByCode($code)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
