<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\GetLegalFrameworkByCode\GetLegalFrameworkByCodeUseCase;
use App\Domain\Entity\LegalFramework;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkId;
use App\Domain\ValueObject\LegalFrameworkName;
use App\Domain\ValueObject\LegalFrameworkOfficialReference;
use App\Domain\ValueObject\LegalFrameworkRegulatoryAuthority;
use PHPUnit\Framework\TestCase;

final class GetLegalFrameworkByCodeUseCaseTest extends TestCase
{
    public function testExecuteReturnsNullForInvalidCodeFormatWithoutQueryingRepository(): void
    {
        $repository = $this->createMock(LegalFrameworkRepositoryInterface::class);
        $repository->expects(self::never())->method('findByCode');

        $useCase = new GetLegalFrameworkByCodeUseCase($repository);
        $result = $useCase->execute('invalid-code');

        self::assertNull($result);
    }

    public function testExecuteQueriesRepositoryForValidCode(): void
    {
        $repository = $this->createMock(LegalFrameworkRepositoryInterface::class);
        $expectedLegalFramework = LegalFramework::create(
            id: LegalFrameworkId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            code: new LegalFrameworkCode('FRAMEWORK-GDPR'),
            name: new LegalFrameworkName('General Data Protection Regulation'),
            officialReference: new LegalFrameworkOfficialReference('OJEU L 119, 4.5.2016'),
            regulatoryAuthority: new LegalFrameworkRegulatoryAuthority('CNIL / EDPB'),
            jurisdictionCode: new JurisdictionCode('JUR-EU-GLOBAL'),
        );

        $repository->expects(self::once())
            ->method('findByCode')
            ->with(self::callback(fn (LegalFrameworkCode $code) => 'FRAMEWORK-GDPR' === $code->value))
            ->willReturn($expectedLegalFramework);

        $useCase = new GetLegalFrameworkByCodeUseCase($repository);
        $result = $useCase->execute('FRAMEWORK-GDPR');

        self::assertSame($expectedLegalFramework, $result);
    }
}
