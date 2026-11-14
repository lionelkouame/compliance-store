<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\Document;
use App\Domain\Entity\Holder;
use App\Domain\Exception\Document\UnknownDocumentHolderException;
use App\Domain\Port\Gateway\CipherGatewayInterface;
use App\Domain\Port\Gateway\StorageGatewayInterface;
use App\Domain\Port\Repository\DocumentRepositoryInterface;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\Port\Service\IdValidatorInterface;
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
        private HolderRepositoryInterface $holders,
        private GeneratorInterface $idGenerator,
        private IdValidatorInterface $idValidator,
    ) {}

    public function execute(UseCaseRequestInterface $request): StoreDocumentResponse
    {
        // Resolved first: nothing is encrypted or stored for an unknown holder.
        $holder = $this->findHolder($request->holderId);

        $fileHash = FileHash::make($request->content);
        $envelope = $this->cipher->encrypt($request->content);
        $documentId = DocumentId::make($this->idGenerator->generate());

        $document = Document::create(
            id: $documentId,
            holderId: $holder->id(),
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

    /**
     * @throws UnknownDocumentHolderException when the id is malformed or matches no holder
     */
    private function findHolder(string $holderId): Holder
    {
        // A malformed id cannot match any holder either.
        if (!$this->idValidator->isValid($holderId)) {
            throw new UnknownDocumentHolderException();
        }

        return $this->holders->findById(HolderId::make($holderId))
            ?? throw new UnknownDocumentHolderException();
    }
}
