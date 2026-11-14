<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\Document;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ReadModel\Document\InfosDocument;
use App\Domain\ValueObject\Document\DocumentId;
use App\Domain\ValueObject\Document\FileHash;
use App\Domain\ValueObject\Holder\HolderId;

/**
 * @implements UseCaseInterface<StoreDocumentRequest, StoreDocumentResponse>
 */
final readonly class StoreDocumentUseCase implements UseCaseInterface
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private GeneratorInterface $idGenerator,
    ) {}

    public function execute(UseCaseRequestInterface $request): StoreDocumentResponse
    {
        $fileHash = FileHash::make($request->content);
        $envelope = $this->cipher->encrypt($request->content);
        $documentId = DocumentId::make($this->idGenerator->generate());

        $document = Document::create(
            id: $documentId,
            holderId: HolderId::make('01a17b0e-e559-7fc6-b4e1-4b96eaa3262c'),
            fileHash: $fileHash,
            wrappedDataKey: $envelope->wrappedDataKey,
        );

        $this->storage->store($documentId, $envelope->payload->value());

        $this->documents->add($document);

        return new StoreDocumentResponse(new InfosDocument(
            id: $document->id(),
            holderId: $document->holderId(),
            fileHash: $document->fileHash(),
            createdAt: $document->createdAt(),
        ));
    }
}
