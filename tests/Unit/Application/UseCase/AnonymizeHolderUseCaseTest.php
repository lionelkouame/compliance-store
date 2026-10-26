<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\AnonymizeHolder\AnonymizeHolderUseCase;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Exception\InvalidHolderStatusTransitionException;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\FileHash;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;
use App\Domain\ValueObject\HolderStatus;
use App\Domain\ValueObject\WrappedDataKey;
use PHPUnit\Framework\TestCase;

final class AnonymizeHolderUseCaseTest extends TestCase
{
    private const HOLDER_ID = '770e8400-e29b-41d4-a716-446655440111';

    public function testExecuteDestroysEveryDocumentThenAnonymizesTheHolder(): void
    {
        $first = $this->document('660e8400-e29b-41d4-a716-446655440001');
        $second = $this->document('660e8400-e29b-41d4-a716-446655440002');

        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::once())->method('update')->with(
            self::callback(static fn (Holder $holder): bool => HolderStatus::Anonymized === $holder->status() && null === $holder->birthDate()),
        );

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->method('findByHolder')->willReturn([$first, $second]);
        $removed = [];
        $documents->expects(self::exactly(2))->method('remove')->willReturnCallback(static function (Document $document) use (&$removed): void {
            $removed[] = $document->id()->value;
        });

        $storage = $this->createMock(StorageGatewayInterface::class);
        $deleted = [];
        $storage->expects(self::exactly(2))->method('delete')->willReturnCallback(static function (DocumentId $id) use (&$deleted): void {
            $deleted[] = $id->value;
        });

        $anonymized = $this->useCase($holders, $documents, $storage)->execute(self::HOLDER_ID);

        self::assertSame(Holder::ANONYMIZED_NAME, (string) $anonymized->name());
        self::assertSame([$first->id()->value, $second->id()->value], $deleted);
        self::assertSame($deleted, $removed);
    }

    public function testExecuteWorksForAHolderWithoutDocuments(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::once())->method('update');

        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('findByHolder')->willReturn([]);

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('delete');

        $anonymized = $this->useCase($holders, $documents, $storage)->execute(self::HOLDER_ID);

        self::assertSame(HolderStatus::Anonymized, $anonymized->status());
    }

    public function testExecuteDestroysNothingWhenNoErasureRequestIsPending(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());
        $holders->expects(self::never())->method('update');

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::never())->method('findByHolder');
        $documents->expects(self::never())->method('remove');

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('delete');

        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->useCase($holders, $documents, $storage)->execute(self::HOLDER_ID);
    }

    public function testExecuteLeavesTheHolderPendingWhenAStorageDeletionFails(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::never())->method('update');

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->method('findByHolder')->willReturn([$this->document('660e8400-e29b-41d4-a716-446655440001')]);
        $documents->expects(self::never())->method('remove');

        $storage = $this->createStub(StorageGatewayInterface::class);
        $storage->method('delete')->willThrowException(new \RuntimeException('bucket unreachable'));

        $this->expectException(\RuntimeException::class);

        $this->useCase($holders, $documents, $storage)->execute(self::HOLDER_ID);
    }

    public function testExecuteFailsForAnUnknownHolder(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, $this->createStub(DocumentRepositoryInterface::class), $this->createStub(StorageGatewayInterface::class))
            ->execute(self::HOLDER_ID);
    }

    private function useCase(
        HolderRepositoryInterface $holders,
        DocumentRepositoryInterface $documents,
        StorageGatewayInterface $storage,
    ): AnonymizeHolderUseCase {
        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(true);

        return new AnonymizeHolderUseCase($holders, $documents, $storage, $validator, $this->createStub(AuditTrailInterface::class));
    }

    private function holder(): Holder
    {
        return Holder::register(
            id: HolderId::fromString(self::HOLDER_ID),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada.lovelace@example.com'),
        );
    }

    private function document(string $id): Document
    {
        return Document::create(
            id: DocumentId::fromString($id),
            holderId: HolderId::fromString(self::HOLDER_ID),
            fileHash: FileHash::fromPlaintext('content '.$id),
            wrappedDataKey: new WrappedDataKey('wrapped-key-bytes'),
        );
    }
}
