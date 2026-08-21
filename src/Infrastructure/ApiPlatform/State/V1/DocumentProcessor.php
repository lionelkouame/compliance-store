<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StoreDocument\StoreDocumentCommand;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\DocumentResource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<mixed, DocumentResource>
 */
final readonly class DocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private StoreDocumentUseCase $useCase,
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DocumentResource
    {
        $input = DocumentResource::fromRequest($this->requestStack->getCurrentRequest());

        $this->validator->validate($input);

        // The Assert constraints on DocumentResource already guarantee these are
        // non-null here (validate() throws otherwise) — narrowing for PHPStan only.
        /** @var UploadedFile $file */
        $file = $input->file;
        /** @var string $documentType */
        $documentType = $input->documentType;
        /** @var string $ownerId */
        $ownerId = $input->ownerId;

        try {
            $document = $this->useCase->execute(new StoreDocumentCommand(
                documentType: $documentType,
                ownerId: $ownerId,
                content: $file->getContent(),
            ));
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return DocumentResource::fromEntity($document);
    }
}
