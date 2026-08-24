<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StoreDocument\StoreDocumentCommand;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\DocumentResource;
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
        $request = $this->requestStack->getCurrentRequest();

        $input = new DocumentResource();
        $file = $request?->files->get('file');
        $input->file = $file instanceof UploadedFile ? $file : null;

        $ownerId = $request?->request->get('ownerId');
        $input->ownerId = \is_string($ownerId) ? $ownerId : null;

        $this->validator->validate($input);

        if (null === $input->file || null === $input->ownerId) {
            throw new UnprocessableEntityHttpException('Missing required document parameters.');
        }

        try {
            $document = $this->useCase->execute(new StoreDocumentCommand(
                ownerId: $input->ownerId,
                content: $input->file->getContent(),
            ));
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return DocumentResource::fromEntity($document);
    }
}
