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

    public function testExecuteReturnsAllJurisdictionsWithoutFilters(): void
    {
        $jurisdictions = [
            $this->jurisdiction('JUR-EU-FRA', 'EU', 'FRA', true),
            $this->jurisdiction('JUR-EU-DEU', 'EU', 'DEU', true),
            $this->jurisdiction('JUR-US-CA', 'NA', 'USA', false),
        ];

        $repository = $this->createStub(JurisdictionRepositoryInterface::class);
        $repository->method('findAll')->willReturn($jurisdictions);

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute();

        self::assertCount(3, $result);
    }

    public function testExecuteFiltersByRegionAndActive(): void
    {
        $euActive = $this->jurisdiction('JUR-EU-FRA', 'EU', 'FRA', true);
        $euInactive = $this->jurisdiction('JUR-EU-DEU', 'EU', 'DEU', false);
        $usActive = $this->jurisdiction('JUR-US-CA', 'NA', 'USA', true);

        $repository = $this->createStub(JurisdictionRepositoryInterface::class);
        $repository->method('findAll')->willReturn([$euActive, $euInactive, $usActive]);

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute(new ListJurisdictionsQuery(region: 'EU', active: true));

        self::assertCount(1, $result);
        self::assertSame($euActive, $result[0]);
    }

    public function testExecuteFiltersByCountry(): void
    {
        $fra = $this->jurisdiction('JUR-EU-FRA', 'EU', 'FRA', true);
        $deu = $this->jurisdiction('JUR-EU-DEU', 'EU', 'DEU', true);

        $repository = $this->createStub(JurisdictionRepositoryInterface::class);
        $repository->method('findAll')->willReturn([$fra, $deu]);

        $useCase = new ListJurisdictionsUseCase($repository);
        $result = $useCase->execute(new ListJurisdictionsQuery(country: 'FRA'));

        self::assertCount(1, $result);
        self::assertSame($fra, $result[0]);
    }
}
