<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Domain\Entity\Holder;
use App\Domain\Entity\IdentityDocument;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\Exception\IdentityDocument\IdentityDocumentAlreadyExistsException;
use App\Domain\Exception\StorageSpace\StorageSpaceCodeAlreadyExistsException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Repository\IdentityDocumentRepositoryInterface;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Document\IdentityDocumentType;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Generic\CountryCode;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\IdentityDocument\ExpiryDate;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentId;
use App\Domain\ValueObject\IdentityDocument\IdentityDocumentNumber;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Simulates the race between two concurrent creations: the second insert is
 * sent straight to the repository, as if it had passed the use case's
 * uniqueness check before the first one was committed. The database rejects
 * it, and the repository must report the business exception (409), not let
 * the DBAL exception surface as a 500 (issue #84).
 */
final class UniqueConstraintTranslationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        foreach ([StorageSpace::class, IdentityDocument::class, Holder::class] as $entity) {
            $entityManager->createQuery('DELETE FROM '.$entity)->execute();
        }
    }

    public function testADuplicateStorageSpaceCodeIsReportedAsAlreadyExisting(): void
    {
        $repository = self::getContainer()->get(StorageSpaceRepositoryInterface::class);
        $repository->add($this->storageSpace('EU-WEST-1'));

        $this->expectException(StorageSpaceCodeAlreadyExistsException::class);
        $this->expectExceptionMessage('"EU-WEST-1"');

        $repository->add($this->storageSpace('EU-WEST-1'));
    }

    public function testADuplicateIdentityDocumentIsReportedAsAlreadyExisting(): void
    {
        $repository = self::getContainer()->get(IdentityDocumentRepositoryInterface::class);
        $repository->add($this->identityDocument('AB123456'));

        try {
            $repository->add($this->identityDocument('AB123456'));
            self::fail('A duplicate identity document must be rejected.');
        } catch (IdentityDocumentAlreadyExistsException $exception) {
            // The number is personal data: it must never reach the message (ADR 0013 §6).
            self::assertStringNotContainsString('AB123456', $exception->getMessage());
        }
    }

    public function testADuplicateHolderEmailIsReportedAsAlreadyExisting(): void
    {
        $repository = self::getContainer()->get(HolderRepositoryInterface::class);
        $repository->add($this->holder('jane.doe@example.com'));

        $this->expectException(HolderAlreadyExistsException::class);

        $repository->add($this->holder('jane.doe@example.com'));
    }

    private function storageSpace(string $code): StorageSpace
    {
        return StorageSpace::create(
            id: StorageSpaceId::fromString(Uuid::v7()->toRfc4122()),
            code: new StorageSpaceCode($code),
            name: new StorageSpaceName('EU West Primary'),
        );
    }

    private function identityDocument(string $number): IdentityDocument
    {
        return IdentityDocument::create(
            id: IdentityDocumentId::make(Uuid::v7()->toRfc4122()),
            type: IdentityDocumentType::Passport,
            country: new CountryCode('FRA'),
            number: new IdentityDocumentNumber($number),
            expiresAt: ExpiryDate::make('2099-01-01'),
        );
    }

    private function holder(string $email): Holder
    {
        return Holder::create(
            HolderId::make(Uuid::v7()->toRfc4122()),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-01-01'),
            Email::make($email),
        );
    }
}
