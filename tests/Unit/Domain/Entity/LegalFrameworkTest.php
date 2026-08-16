<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\LegalFramework;
use App\Domain\ValueObject\JurisdictionCode;
use App\Domain\ValueObject\LegalFrameworkCode;
use App\Domain\ValueObject\LegalFrameworkId;
use App\Domain\ValueObject\LegalFrameworkName;
use App\Domain\ValueObject\LegalFrameworkOfficialReference;
use App\Domain\ValueObject\LegalFrameworkRegulatoryAuthority;
use PHPUnit\Framework\TestCase;

final class LegalFrameworkTest extends TestCase
{
    private function createLegalFramework(bool $active = true): LegalFramework
    {
        return LegalFramework::create(
            id: LegalFrameworkId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            code: new LegalFrameworkCode('FRAMEWORK-GDPR'),
            name: new LegalFrameworkName('General Data Protection Regulation (EU 2016/679)'),
            officialReference: new LegalFrameworkOfficialReference('OJEU L 119, 4.5.2016'),
            regulatoryAuthority: new LegalFrameworkRegulatoryAuthority('CNIL / EDPB'),
            jurisdictionCode: new JurisdictionCode('JUR-EU-GLOBAL'),
            active: $active,
        );
    }

    public function testCreateBuildsAnActiveLegalFrameworkByDefault(): void
    {
        $legalFramework = $this->createLegalFramework();

        self::assertSame('FRAMEWORK-GDPR', $legalFramework->code()->value);
        self::assertSame('General Data Protection Regulation (EU 2016/679)', $legalFramework->name()->value);
        self::assertSame('OJEU L 119, 4.5.2016', $legalFramework->officialReference()->value);
        self::assertSame('CNIL / EDPB', $legalFramework->regulatoryAuthority()->value);
        self::assertSame('JUR-EU-GLOBAL', $legalFramework->jurisdictionCode()->value);
        self::assertTrue($legalFramework->isActive());
        self::assertEquals($legalFramework->createdAt(), $legalFramework->updatedAt());
    }

    public function testDeactivateTogglesActiveAndTouchesUpdatedAt(): void
    {
        $legalFramework = $this->createLegalFramework();
        $createdAt = $legalFramework->createdAt();

        $legalFramework->deactivate();

        self::assertFalse($legalFramework->isActive());
        self::assertSame($createdAt, $legalFramework->createdAt());
        self::assertGreaterThanOrEqual($createdAt, $legalFramework->updatedAt());
    }

    public function testActivateTogglesActiveBackOn(): void
    {
        $legalFramework = $this->createLegalFramework(active: false);

        $legalFramework->activate();

        self::assertTrue($legalFramework->isActive());
    }
}
