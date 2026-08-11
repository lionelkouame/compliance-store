<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ApiPlatform\Validator;

use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Infrastructure\ApiPlatform\Validator\Constraint\AssertJurisdictionCodeUnique;
use App\Infrastructure\ApiPlatform\Validator\ConstraintValidator\AssertJurisdictionCodeUniqueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AssertJurisdictionCodeUniqueValidator>
 */
final class AssertJurisdictionCodeUniqueValidatorTest extends ConstraintValidatorTestCase
{
    private JurisdictionRepositoryInterface&MockObject $repository;

    protected function createValidator(): AssertJurisdictionCodeUniqueValidator
    {
        $this->repository = $this->createMock(JurisdictionRepositoryInterface::class);

        return new AssertJurisdictionCodeUniqueValidator($this->repository);
    }

    public function testItDoesNothingWhenValueIsNull(): void
    {
        $this->repository->expects($this->never())->method('existsByCode');

        $this->validator->validate(null, new AssertJurisdictionCodeUnique());

        $this->assertNoViolation();
    }

    public function testItDoesNothingWhenValueIsEmptyString(): void
    {
        $this->repository->expects($this->never())->method('existsByCode');

        $this->validator->validate('', new AssertJurisdictionCodeUnique());

        $this->assertNoViolation();
    }

    public function testItAddsViolationWhenCodeAlreadyExists(): void
    {
        $this->repository->expects($this->once())
            ->method('existsByCode')
            ->with($this->callback(fn (JurisdictionCode $code) => 'JUR-EU-FRA' === $code->value))
            ->willReturn(true);

        $constraint = new AssertJurisdictionCodeUnique();

        $this->validator->validate('JUR-EU-FRA', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'JUR-EU-FRA')
            ->assertRaised();
    }

    public function testItDoesNotAddViolationWhenCodeIsUnique(): void
    {
        $this->repository->expects($this->once())
            ->method('existsByCode')
            ->willReturn(false);

        $this->validator->validate('JUR-EU-NEW', new AssertJurisdictionCodeUnique());

        $this->assertNoViolation();
    }
}
