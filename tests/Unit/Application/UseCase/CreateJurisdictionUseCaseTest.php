<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\CreateJurisdiction\CreateJurisdictionCommand;
use App\Application\UseCase\CreateJurisdiction\CreateJurisdictionUseCase;
use App\Domain\Exception\JurisdictionAlreadyExistsException;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\Port\Service\JurisdictionIdGeneratorInterface;
use App\Domain\ValueObject\JurisdictionCode;
use PHPUnit\Framework\TestCase;

final class CreateJurisdictionUseCaseTest extends TestCase
{
    /**
     * Domain Double-Lock Guard (ADR 0005): non-HTTP callers (CLI, async workers)
     * bypass the DTO's declarative AssertJurisdictionCodeUnique constraint, so the
     * Use Case must enforce uniqueness itself.
     */
    public function testExecuteThrowsWhenCodeAlreadyExists(): void
    {
        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('existsByCode')
            ->with(self::callback(fn (JurisdictionCode $code) => 'JUR-EU-FRA' === $code->value))
            ->willReturn(true);
        $repository->expects(self::never())->method('add');

        $idGenerator = $this->createMock(JurisdictionIdGeneratorInterface::class);
        $idGenerator->expects(self::never())->method('generate');

        $useCase = new CreateJurisdictionUseCase($repository, $idGenerator);

        $this->expectException(JurisdictionAlreadyExistsException::class);

        $useCase->execute(new CreateJurisdictionCommand(
            code: 'JUR-EU-FRA',
            label: 'France (European Union)',
            region: 'EU',
            country: 'FRA',
            subRegion: null,
            applicableFrameworks: ['GDPR'],
        ));
    }
}
