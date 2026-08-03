<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StoreDocument\StoreDocumentCommand;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\DocumentResource;
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
        $input->file = $request?->files->get('file');
        $input->documentType = $request?->request->get('documentType');
        $input->ownerId = $request?->request->get('ownerId');
        $input->country = $request?->request->get('country');
        $retentionYears = $request?->request->get('retentionYears');
        $input->retentionYears = is_numeric($retentionYears) ? (int) $retentionYears : null;

        $this->validator->validate($input);

        try {
            $document = $this->useCase->execute(new StoreDocumentCommand(
                documentType: $input->documentType,
                ownerId: $input->ownerId,
                country: $input->country,
                retentionYears: $input->retentionYears,
                content: $input->file->getContent(),
            ));
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return DocumentResource::fromEntity($document);
    }
}
