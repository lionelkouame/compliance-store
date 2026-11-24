<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Holder;

use App\Application\UseCase\Holder\AnonymizeHolder\AnonymizeHolderRequest;
use App\Application\UseCase\Holder\AnonymizeHolder\AnonymizeHolderUseCase;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Event\Holder\HolderAnonymized;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Exception\Holder\InvalidHolderStatusTransitionException;
use App\Domain\Port\Event\DomainEventPublisher;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Document\WrappedDataKey;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;
use PHPUnit\Framework\TestCase;

final class AnonymizeHolderUseCaseTest extends TestCase
{
    private const string ID = '01a17b0e-e559-7fc6-b4e1-4b96eaa3262c';

    public function testExecuteDeletesTheDocumentsThenAnonymizesTheHolder(): void
    {
        $documents = [$this->document('01a17b0e-e559-7fc6-b4e1-000000000001'), $this->document('01a17b0e-e559-7fc6-b4e1-000000000002')];
        $calls = [];

        $documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $documentRepository->method('findByHolder')->willReturn($documents);
        $documentRepository->expects(self::exactly(2))->method('remove')
            ->willReturnCallback(static function (Document $document) use (&$calls): void {
                $calls[] = 'remove '.$document->id()->value();
            });

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::exactly(2))->method('delete')
            ->willReturnCallback(static function (DocumentId $id) use (&$calls): void {
                $calls[] = 'delete '.$id->value();
            });

        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::once())->method('update')
            ->willReturnCallback(static function (Holder $holder) use (&$calls): void {
                $calls[] = 'update '.$holder->status()->value;
            });

        $publisher = $this->createMock(DomainEventPublisher::class);
        $publisher->expects(self::once())->method('publish')
            ->with(self::isInstanceOf(HolderAnonymized::class));

        $readModel = $this->useCase($holders, $documentRepository, $storage, $publisher)
            ->execute(new AnonymizeHolderRequest(self::ID))
            ->readModel;

        // Contenu supprimé avant la ligne qui porte la clé, et le titulaire seulement à la fin.
        self::assertSame([
            'delete 01a17b0e-e559-7fc6-b4e1-000000000001',
            'remove 01a17b0e-e559-7fc6-b4e1-000000000001',
            'delete 01a17b0e-e559-7fc6-b4e1-000000000002',
            'remove 01a17b0e-e559-7fc6-b4e1-000000000002',
            'update anonymized',
        ], $calls);
        self::assertSame(HolderStatus::Anonymized, $readModel->status);
        self::assertSame(Holder::ANONYMIZED_NAME, $readModel->name->value);
        self::assertStringEndsWith('@anonymized.invalid', $readModel->email->value);
    }

    public function testExecuteDeletesNothingWithoutAPendingErasureRequest(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder());
        $holders->expects(self::never())->method('update');

        $documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $documentRepository->expects(self::never())->method('findByHolder');
        $documentRepository->expects(self::never())->method('remove');

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('delete');

        $this->expectException(InvalidHolderStatusTransitionException::class);

        $this->useCase($holders, $documentRepository, $storage, $this->createStub(DomainEventPublisher::class))
            ->execute(new AnonymizeHolderRequest(self::ID));
    }

    public function testAStorageFailureLeavesTheHolderPendingSoThatTheErasureCanBeRetried(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn($this->holder()->requestErasure());
        $holders->expects(self::never())->method('update');

        $documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $documentRepository->method('findByHolder')->willReturn([$this->document('01a17b0e-e559-7fc6-b4e1-000000000001')]);
        $documentRepository->expects(self::never())->method('remove');

        $storage = $this->createStub(StorageGatewayInterface::class);
        $storage->method('delete')->willThrowException(new \RuntimeException('Storage unavailable.'));

        $this->expectException(\RuntimeException::class);

        $this->useCase($holders, $documentRepository, $storage, $this->createStub(DomainEventPublisher::class))
            ->execute(new AnonymizeHolderRequest(self::ID));
    }

    public function testExecuteThrowsWhenNoHolderMatchesTheId(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, $this->createStub(DocumentRepositoryInterface::class), $this->createStub(StorageGatewayInterface::class), $this->createStub(DomainEventPublisher::class))
            ->execute(new AnonymizeHolderRequest(self::ID));
    }

    private function useCase(
        HolderRepositoryInterface $holders,
        DocumentRepositoryInterface $documents,
        StorageGatewayInterface $storage,
        DomainEventPublisher $publisher,
    ): AnonymizeHolderUseCase {
        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn(true);

        return new AnonymizeHolderUseCase($holders, $documents, $storage, $idValidator, $publisher);
    }

    private function holder(): Holder
    {
        return Holder::create(
            HolderId::make(self::ID),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-07-14'),
            Email::make('jane@example.com'),
        );
    }

    private function document(string $id): Document
    {
        return Document::create(
            id: DocumentId::make($id),
            holderId: HolderId::make(self::ID),
            fileHash: FileHash::fromDigest(str_repeat('a', 64)),
            wrappedDataKey: WrappedDataKey::make('wrapped-key'),
        );
    }
}
