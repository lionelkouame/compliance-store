<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Validation;

use App\Domain\Port\Repository\RegulatoryScopeRepositoryInterface;
use App\Domain\ValueObject\RegulatoryScopeCode;
use App\Infrastructure\Validation\Constraint\AssertRegulatoryScopeCodeUnique;
use App\Infrastructure\Validation\ConstraintValidator\AssertRegulatoryScopeCodeUniqueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AssertRegulatoryScopeCodeUniqueValidator>
 */
final class AssertRegulatoryScopeCodeUniqueValidatorTest extends ConstraintValidatorTestCase
{
    private RegulatoryScopeRepositoryInterface&MockObject $repository;

    protected function createValidator(): AssertRegulatoryScopeCodeUniqueValidator
    {
        $this->repository = $this->createMock(RegulatoryScopeRepositoryInterface::class);

        return new AssertRegulatoryScopeCodeUniqueValidator($this->repository);
    }

    public function testItDoesNothingWhenValueIsNull(): void
    {
        $this->repository->expects($this->never())->method('existsByCode');

        $this->validator->validate(null, new AssertRegulatoryScopeCodeUnique());

        $this->assertNoViolation();
    }

    public function testItDoesNothingWhenValueIsEmptyString(): void
    {
        $this->repository->expects($this->never())->method('existsByCode');

        $this->validator->validate('', new AssertRegulatoryScopeCodeUnique());

        $this->assertNoViolation();
    }

    public function testItAddsViolationWhenCodeAlreadyExists(): void
    {
        $this->repository->expects($this->once())
            ->method('existsByCode')
            ->with($this->callback(fn (RegulatoryScopeCode $code) => 'KYC_INDIVIDUAL' === $code->value))
            ->willReturn(true);

        $constraint = new AssertRegulatoryScopeCodeUnique();

        $this->validator->validate('KYC_INDIVIDUAL', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'KYC_INDIVIDUAL')
            ->assertRaised();
    }

    public function testItDoesNotAddViolationWhenCodeIsUnique(): void
    {
        $this->repository->expects($this->once())
            ->method('existsByCode')
            ->willReturn(false);

        $this->validator->validate('NEW_SCOPE', new AssertRegulatoryScopeCodeUnique());

        $this->assertNoViolation();
    }
}
