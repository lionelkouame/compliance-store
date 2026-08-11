<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\GetJurisdictionByCode\GetJurisdictionByCodeUseCase;
use App\Domain\Entity\Jurisdiction;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\ApplicableFrameworks;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionId;
use App\Domain\ValueObject\JurisdictionLabel;
use App\Domain\ValueObject\JurisdictionRegion;
use PHPUnit\Framework\TestCase;

final class GetJurisdictionByCodeUseCaseTest extends TestCase
{
    public function testExecuteReturnsNullForInvalidCodeFormatWithoutQueryingRepository(): void
    {
        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::never())->method('findByCode');

        $useCase = new GetJurisdictionByCodeUseCase($repository);
        $result = $useCase->execute('invalid-code');

        self::assertNull($result);
    }

    public function testExecuteQueriesRepositoryForValidCode(): void
    {
        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $expectedJurisdiction = Jurisdiction::create(
            id: JurisdictionId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            code: new JurisdictionCode('JUR-EU-FRA'),
            label: new JurisdictionLabel('France (European Union)'),
            region: new JurisdictionRegion('EU'),
            country: null,
            subRegion: null,
            applicableFrameworks: new ApplicableFrameworks(),
        );

        $repository->expects(self::once())
            ->method('findByCode')
            ->with(self::callback(fn (JurisdictionCode $code) => 'JUR-EU-FRA' === $code->value))
            ->willReturn($expectedJurisdiction);

        $useCase = new GetJurisdictionByCodeUseCase($repository);
        $result = $useCase->execute('JUR-EU-FRA');

        self::assertSame($expectedJurisdiction, $result);
    }
}
