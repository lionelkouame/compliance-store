<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\StoreDocument;

use App\Application\Service\Document\DocumentVault;
use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\Holder;
use App\Domain\Exception\Document\UnknownDocumentHolderException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ReadModel\Document\InfosDocument;
use App\Domain\ValueObject\Shared\Identifier\HolderId;

/**
 * @implements UseCaseInterface<StoreDocumentRequest, StoreDocumentResponse>
 */
final readonly class StoreDocumentUseCase implements UseCaseInterface
{
    public function __construct(
        private DocumentVault $vault,
        private HolderRepositoryInterface $holders,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @throws UnknownDocumentHolderException|\Throwable when the holder id is malformed or matches no holder
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest): StoreDocumentResponse
    {
        // Resolved first: nothing is encrypted or stored for an unknown holder.
        $holder = $this->findHolder($registerIdentityDocumentRequest->holderId);

        $document = $this->vault->store($holder->id(), $registerIdentityDocumentRequest->content);

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

        return $this->holders->findById(HolderId::make($holderId)) // @phpstan-ignore missingType.checkedException (id validated above)
            ?? throw new UnknownDocumentHolderException();
    }
}
