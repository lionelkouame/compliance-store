<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Document;

use App\Application\UseCase\Document\StoreDocument\StoreDocumentRequest;
use App\Application\UseCase\Document\StoreDocument\StoreDocumentUseCase;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Exception\Document\UnknownDocumentHolderException;
use App\Domain\Exception\InvalidDataException;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Document\WrappedDataKey;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Generic\EncryptedPayload;
use App\Domain\ValueObject\Generic\EncryptionEnvelope;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use PHPUnit\Framework\TestCase;

final class StoreDocumentUseCaseTest extends TestCase
{
    private const DOCUMENT_ID = '660e8400-e29b-41d4-a716-446655440000';
    private const HOLDER_ID = '0192f0c1-0000-7000-8000-0000000000aa';

    public function testExecuteEncryptsStoresAndPersistsADocumentForItsHolder(): void
    {
        $envelope = new EncryptionEnvelope(
            payload: EncryptedPayload::make('cipher-bytes'),
            wrappedDataKey: WrappedDataKey::make('wrapped-key-bytes'),
        );

        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::once())->method('encrypt')->with('plaintext content')->willReturn($envelope);

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::once())->method('store')->with(
            self::callback(static fn ($id) => self::DOCUMENT_ID === $id->value()),
            'cipher-bytes',
        );

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::once())->method('add')->with(
            self::callback(static fn (Document $document): bool => self::HOLDER_ID === $document->holderId()->value()),
        );

        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::once())->method('findById')
            ->with(self::callback(static fn (HolderId $id): bool => self::HOLDER_ID === $id->value()))
            ->willReturn($this->holder());

        $response = $this->useCase($cipher, $storage, $documents, $holders, validId: true)
            ->execute(new StoreDocumentRequest(holderId: self::HOLDER_ID, content: 'plaintext content'));

        self::assertSame(self::DOCUMENT_ID, $response->readModel->id->value());
        self::assertSame(self::HOLDER_ID, $response->readModel->holderId->value());
        self::assertSame(hash('sha256', 'plaintext content'), $response->readModel->fileHash->value());
    }

    public function testExecuteRejectsAnUnknownHolderBeforeEncryptingOrStoringAnything(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $this->expectException(UnknownDocumentHolderException::class);

        $this->useCase(...$this->untouchedGateways(), holders: $holders, validId: true)
            ->execute(new StoreDocumentRequest(holderId: self::HOLDER_ID, content: 'plaintext content'));
    }

    public function testExecuteRejectsAMalformedHolderIdWithoutQueryingTheRepository(): void
    {
        $holders = $this->createMock(HolderRepositoryInterface::class);
        $holders->expects(self::never())->method('findById');

        $this->expectException(UnknownDocumentHolderException::class);

        $this->useCase(...$this->untouchedGateways(), holders: $holders, validId: false)
            ->execute(new StoreDocumentRequest(holderId: 'not-a-uuid', content: 'plaintext content'));
    }

    public function testAnUnknownHolderBelongsToTheInvalidDataCategory(): void
    {
        self::assertInstanceOf(InvalidDataException::class, new UnknownDocumentHolderException());
    }

    /**
     * Gateways and repository that must never be called when the holder is rejected.
     *
     * @return array{cipher: CipherGatewayInterface, storage: StorageGatewayInterface, documents: DocumentRepositoryInterface}
     */
    private function untouchedGateways(): array
    {
        $cipher = $this->createMock(CipherGatewayInterface::class);
        $cipher->expects(self::never())->method('encrypt');

        $storage = $this->createMock(StorageGatewayInterface::class);
        $storage->expects(self::never())->method('store');

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::never())->method('add');

        return ['cipher' => $cipher, 'storage' => $storage, 'documents' => $documents];
    }

    private function useCase(
        CipherGatewayInterface $cipher,
        StorageGatewayInterface $storage,
        DocumentRepositoryInterface $documents,
        HolderRepositoryInterface $holders,
        bool $validId,
    ): StoreDocumentUseCase {
        $idGenerator = $this->createStub(GeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::DOCUMENT_ID);

        $idValidator = $this->createStub(IdValidatorInterface::class);
        $idValidator->method('isValid')->willReturn($validId);

        return new StoreDocumentUseCase(
            cipher: $cipher,
            storage: $storage,
            documents: $documents,
            holders: $holders,
            idGenerator: $idGenerator,
            idValidator: $idValidator,
        );
    }

    private function holder(): Holder
    {
        return Holder::create(
            HolderId::make(self::HOLDER_ID),
            HolderName::make('Jane Doe'),
            BirthDate::make('1990-01-01'),
            Email::make('jane@example.com'),
        );
    }
}
