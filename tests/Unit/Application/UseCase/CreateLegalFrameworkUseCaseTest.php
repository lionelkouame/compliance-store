<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\CreateLegalFramework\CreateLegalFrameworkCommand;
use App\Application\UseCase\CreateLegalFramework\CreateLegalFrameworkUseCase;
use App\Domain\Exception\LegalFrameworkAlreadyExistsException;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\Port\Service\LegalFrameworkIdGeneratorInterface;
use App\Domain\ValueObject\LegalFrameworkCode;
use PHPUnit\Framework\TestCase;

final class CreateLegalFrameworkUseCaseTest extends TestCase
{
    /**
     * Domain Double-Lock Guard (ADR 0005): non-HTTP callers (CLI, async workers)
     * bypass the DTO's declarative AssertLegalFrameworkCodeUnique constraint, so
     * the Use Case must enforce uniqueness itself.
     */
    public function testExecuteThrowsWhenCodeAlreadyExists(): void
    {
        $repository = $this->createMock(LegalFrameworkRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('existsByCode')
            ->with(self::callback(fn (LegalFrameworkCode $code) => 'FRAMEWORK-GDPR' === $code->value))
            ->willReturn(true);
        $repository->expects(self::never())->method('add');

        $idGenerator = $this->createMock(LegalFrameworkIdGeneratorInterface::class);
        $idGenerator->expects(self::never())->method('generate');

        $useCase = new CreateLegalFrameworkUseCase($repository, $idGenerator);

        $this->expectException(LegalFrameworkAlreadyExistsException::class);

        $useCase->execute(new CreateLegalFrameworkCommand(
            code: 'FRAMEWORK-GDPR',
            name: 'General Data Protection Regulation',
            officialReference: 'OJEU L 119, 4.5.2016',
            regulatoryAuthority: 'CNIL / EDPB',
            jurisdictionCode: 'JUR-EU-GLOBAL',
        ));
    }
}
