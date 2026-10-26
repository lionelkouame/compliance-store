<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase;

use App\Application\UseCase\ListHolderDocuments\ListHolderDocumentsUseCase;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\DocumentId;
use App\Domain\ValueObject\FileHash;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;
use App\Domain\ValueObject\WrappedDataKey;
use PHPUnit\Framework\TestCase;

final class ListHolderDocumentsUseCaseTest extends TestCase
{
    private const HOLDER_ID = '770e8400-e29b-41d4-a716-446655440111';

    public function testExecuteReturnsTheDocumentsOfAKnownHolder(): void
    {
        $document = Document::create(
            id: DocumentId::fromString('660e8400-e29b-41d4-a716-446655440000'),
            holderId: HolderId::fromString(self::HOLDER_ID),
            fileHash: FileHash::fromPlaintext('content'),
            wrappedDataKey: new WrappedDataKey('wrapped-key-bytes'),
        );

        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(Holder::register(
            id: HolderId::fromString(self::HOLDER_ID),
            name: new HolderName('Ada Lovelace'),
            birthDate: new BirthDate(new \DateTimeImmutable('1990-01-01', new \DateTimeZone('UTC'))),
            email: new Email('ada.lovelace@example.com'),
        ));

        $documents = $this->createStub(DocumentRepositoryInterface::class);
        $documents->method('findByHolder')->willReturn([$document]);

        self::assertSame([$document], $this->useCase($holders, $documents)->execute(self::HOLDER_ID));
    }

    public function testExecuteFailsForAnUnknownHolderInsteadOfReturningAnEmptyList(): void
    {
        $holders = $this->createStub(HolderRepositoryInterface::class);
        $holders->method('findById')->willReturn(null);

        $documents = $this->createMock(DocumentRepositoryInterface::class);
        $documents->expects(self::never())->method('findByHolder');

        $this->expectException(HolderNotFoundException::class);

        $this->useCase($holders, $documents)->execute(self::HOLDER_ID);
    }

    private function useCase(HolderRepositoryInterface $holders, DocumentRepositoryInterface $documents): ListHolderDocumentsUseCase
    {
        $validator = $this->createStub(IdValidatorInterface::class);
        $validator->method('isValid')->willReturn(true);

        return new ListHolderDocumentsUseCase($holders, $documents, $validator);
    }
}
