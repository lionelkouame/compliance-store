<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\ListJurisdictions\ListJurisdictionsQuery;
use App\Application\UseCase\ListJurisdictions\ListJurisdictionsUseCase;
use App\Domain\Entity\Jurisdiction;
use App\Domain\Port\Repository\JurisdictionRepositoryInterface;
use App\Domain\ValueObject\ApplicableFrameworks;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\JurisdictionCountry;
use App\Domain\ValueObject\JurisdictionId;
use App\Domain\ValueObject\JurisdictionLabel;
use App\Domain\ValueObject\JurisdictionRegion;
use PHPUnit\Framework\TestCase;

final class ListJurisdictionsUseCaseTest extends TestCase
{
    private function jurisdiction(string $code, string $region, ?string $country, bool $active): Jurisdiction
    {
        return Jurisdiction::create(
            id: JurisdictionId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            code: new JurisdictionCode($code),
            label: new JurisdictionLabel($code),
            region: new JurisdictionRegion($region),
            country: null !== $country ? new JurisdictionCountry($country) : null,
            subRegion: null,
            applicableFrameworks: new ApplicableFrameworks(),
            active: $active,
        );
    }

    public function testExecuteDelegatesToRepositoryWithoutFilters(): void
    {
        $jurisdictions = [
            $this->jurisdiction('JUR-EU-FRA', 'EU', 'FRA', true),
            $this->jurisdiction('JUR-EU-DEU', 'EU', 'DEU', true),
            $this->jurisdiction('JUR-US-CA', 'NA', 'USA', false),
        ];

        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findAllMatching')
            ->with(null, null, null)
            ->willReturn($jurisdictions);

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute();

        self::assertSame($jurisdictions, $result);
    }

    public function testExecuteDelegatesRegionAndActiveFiltersToRepositoryAsValueObjects(): void
    {
        $euActive = $this->jurisdiction('JUR-EU-FRA', 'EU', 'FRA', true);

        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findAllMatching')
            ->with(
                self::callback(fn (JurisdictionRegion $region) => 'EU' === $region->value),
                null,
                true,
            )
            ->willReturn([$euActive]);

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute(new ListJurisdictionsQuery(region: 'EU', active: true));

        self::assertCount(1, $result);
        self::assertSame($euActive, $result[0]);
    }

    public function testExecuteDelegatesCountryFilterToRepositoryAsValueObject(): void
    {
        $fra = $this->jurisdiction('JUR-EU-FRA', 'EU', 'FRA', true);

        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findAllMatching')
            ->with(
                null,
                self::callback(fn (JurisdictionCountry $country) => 'FRA' === $country->value),
                null,
            )
            ->willReturn([$fra]);

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute(new ListJurisdictionsQuery(country: 'FRA'));

        self::assertCount(1, $result);
        self::assertSame($fra, $result[0]);
    }

    public function testExecuteReturnsEmptyArrayWithoutQueryingRepositoryForAMalformedRegionFilter(): void
    {
        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::never())->method('findAllMatching');

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute(new ListJurisdictionsQuery(region: 'not-a-valid-region'));

        self::assertSame([], $result);
    }

    public function testExecuteReturnsEmptyArrayWithoutQueryingRepositoryForAMalformedCountryFilter(): void
    {
        $repository = $this->createMock(JurisdictionRepositoryInterface::class);
        $repository->expects(self::never())->method('findAllMatching');

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute(new ListJurisdictionsQuery(country: 'FR'));

        self::assertSame([], $result);
    }
}
