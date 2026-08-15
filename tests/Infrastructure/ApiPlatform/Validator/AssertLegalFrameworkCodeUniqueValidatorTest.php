<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ApiPlatform\Validator;

use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertLegalFrameworkCodeUnique;
use App\Infrastructure\ApiPlatform\Validator\ConstraintValidator\AssertLegalFrameworkCodeUniqueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AssertLegalFrameworkCodeUniqueValidator>
 */
final class AssertLegalFrameworkCodeUniqueValidatorTest extends ConstraintValidatorTestCase
{
    private LegalFrameworkRepositoryInterface&MockObject $repository;

    protected function createValidator(): AssertLegalFrameworkCodeUniqueValidator
    {
        $this->repository = $this->createMock(LegalFrameworkRepositoryInterface::class);

        return new AssertLegalFrameworkCodeUniqueValidator($this->repository);
    }

    public function testItDoesNothingWhenValueIsNull(): void
    {
        $this->repository->expects($this->never())->method('existsByCode');

        $this->validator->validate(null, new AssertLegalFrameworkCodeUnique());

        $this->assertNoViolation();
    }

    public function testItDoesNothingWhenValueIsEmptyString(): void
    {
        $this->repository->expects($this->never())->method('existsByCode');

        $this->validator->validate('', new AssertLegalFrameworkCodeUnique());

        $this->assertNoViolation();
    }

    public function testItAddsViolationWhenCodeAlreadyExists(): void
    {
        $this->repository->expects($this->once())
            ->method('existsByCode')
            ->with($this->callback(fn (LegalFrameworkCode $code) => 'FRAMEWORK-GDPR' === $code->value))
            ->willReturn(true);

        $constraint = new AssertLegalFrameworkCodeUnique();

        $this->validator->validate('FRAMEWORK-GDPR', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'FRAMEWORK-GDPR')
            ->assertRaised();
    }

    public function testItDoesNotAddViolationWhenCodeIsUnique(): void
    {
        $this->repository->expects($this->once())
            ->method('existsByCode')
            ->willReturn(false);

        $this->validator->validate('FRAMEWORK-NEW', new AssertLegalFrameworkCodeUnique());

        $this->assertNoViolation();
    }
}
