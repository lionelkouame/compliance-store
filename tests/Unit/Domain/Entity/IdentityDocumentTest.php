<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\IdentityDocument;
use App\Domain\Exception\IdentityDocumentExpiredException;
use App\Domain\ValueObject\CountryCode;
use App\Domain\ValueObject\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocumentNumber;
use App\Domain\ValueObject\IdentityDocumentType;
use PHPUnit\Framework\TestCase;

final class IdentityDocumentTest extends TestCase
{
    public function testCreateExposesItsAttributes(): void
    {
        $id = new IdentityDocumentId('0192f0c1-0000-7000-8000-000000000001');
        $expiresAt = new \DateTimeImmutable('+5 years', new \DateTimeZone('UTC'));

        $document = $this->create($id, $expiresAt);

        self::assertTrue($document->id()->equals($id));
        self::assertSame(IdentityDocumentType::Passport, $document->type());
        self::assertSame('FRA', $document->country()->value);
        self::assertSame('18AB12345', $document->number()->value);
        self::assertSame($expiresAt, $document->expiresAt());
        self::assertSame('UTC', $document->createdAt()->getTimezone()->getName());
    }

    public function testADocumentExpiringTodayIsStillValid(): void
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));

        self::assertSame($today, $this->create(expiresAt: $today)->expiresAt());
    }

    public function testCreateRejectsAnExpiredDocument(): void
    {
        $this->expectException(IdentityDocumentExpiredException::class);

        $this->create(expiresAt: new \DateTimeImmutable('yesterday', new \DateTimeZone('UTC')));
    }

    private function create(?IdentityDocumentId $id = null, ?\DateTimeImmutable $expiresAt = null): IdentityDocument
    {
        return IdentityDocument::create(
            id: $id ?? new IdentityDocumentId('0192f0c1-0000-7000-8000-000000000001'),
            type: IdentityDocumentType::Passport,
            country: new CountryCode('FRA'),
            number: new IdentityDocumentNumber('18AB12345'),
            expiresAt: $expiresAt ?? new \DateTimeImmutable('+5 years', new \DateTimeZone('UTC')),
        );
    }
}
