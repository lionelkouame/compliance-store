<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Domain\Entity\Holder;
use App\Domain\Entity\StorageSpace;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Exception\StorageSpace\InvalidStorageSpaceStatusTransitionException;
use App\Domain\Port\Persistence\TransactionInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Repository\StorageSpaceRepositoryInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use App\Domain\ValueObject\Shared\Generic\BirthDate;
use App\Domain\ValueObject\Shared\Identifier\HolderId;
use App\Domain\ValueObject\Shared\Identifier\StorageSpaceId;
use App\Domain\ValueObject\StorageSpace\StorageSpaceCode;
use App\Domain\ValueObject\StorageSpace\StorageSpaceName;
use App\Domain\ValueObject\StorageSpace\StorageSpaceStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Transactions et accès concurrents sur une vraie base (issue #90).
 */
final class TransactionAndConcurrencyTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM '.StorageSpace::class)->execute();
        $entityManager->createQuery('DELETE FROM '.Holder::class)->execute();
    }

    public function testATransactionCommitsEveryWriteOfTheOperation(): void
    {
        $storageSpaces = self::getContainer()->get(StorageSpaceRepositoryInterface::class);

        self::getContainer()->get(TransactionInterface::class)->run(function () use ($storageSpaces): void {
            $storageSpaces->add($this->storageSpace('EU-WEST-1'));
            $storageSpaces->add($this->storageSpace('EU-WEST-2'));
        });

        self::assertCount(2, $storageSpaces->findAll());
    }

    public function testAFailureRollsBackTheWritesAlreadyFlushedByTheRepositories(): void
    {
        $storageSpaces = self::getContainer()->get(StorageSpaceRepositoryInterface::class);

        try {
            self::getContainer()->get(TransactionInterface::class)->run(function () use ($storageSpaces): void {
                // add() fait son propre flush() : il doit pourtant être annulé avec le reste.
                $storageSpaces->add($this->storageSpace('EU-WEST-1'));

                throw new \RuntimeException('Failure after the first write.');
            });
            self::fail('The exception must be rethrown.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Failure after the first write.', $exception->getMessage());
        }

        $count = self::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM storage_space');
        self::assertSame(0, (int) $count);
    }

    public function testOfTwoConcurrentHolderTransitionsOnlyTheFirstOneIsWritten(): void
    {
        $holders = self::getContainer()->get(HolderRepositoryInterface::class);
        $holder = Holder::create(
            HolderId::make(Uuid::v7()->toRfc4122()),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-07-14'),
            Email::make('jane.concurrency@example.com'),
        );
        $holders->add($holder);

        // Deux requêtes ont lu le titulaire « active » et calculent la même transition.
        $first = $holder->requestErasure();
        $second = $holder->requestErasure();

        $holders->update($first, HolderStatus::Active);

        $this->expectException(InvalidHolderStatusTransitionException::class);
        $this->expectExceptionMessage('changed concurrently');

        $holders->update($second, HolderStatus::Active);
    }

    public function testOfTwoConcurrentStorageSpaceTransitionsOnlyTheFirstOneIsWritten(): void
    {
        $storageSpaces = self::getContainer()->get(StorageSpaceRepositoryInterface::class);
        $storageSpace = $this->storageSpace('EU-WEST-1');
        $storageSpaces->add($storageSpace);

        $storageSpaces->update($storageSpace->withStatus(StorageSpaceStatus::Inactive), StorageSpaceStatus::Active);

        try {
            // Calculée sur l'état « active » lu avant la première écriture : périmée.
            $storageSpaces->update($storageSpace->withStatus(StorageSpaceStatus::Archived), StorageSpaceStatus::Active);
            self::fail('A transition computed from a stale status must be refused.');
        } catch (InvalidStorageSpaceStatusTransitionException) {
        }

        self::assertSame(StorageSpaceStatus::Inactive, $storageSpaces->getById($storageSpace->id)->status);
    }

    private function storageSpace(string $code): StorageSpace
    {
        return StorageSpace::create(
            id: StorageSpaceId::fromString(Uuid::v7()->toRfc4122()),
            code: new StorageSpaceCode($code),
            name: new StorageSpaceName('EU West'),
        );
    }
}
