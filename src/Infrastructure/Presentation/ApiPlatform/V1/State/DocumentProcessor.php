<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StoreDocument\StoreDocumentCommand;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Domain\Exception\HolderNotActiveException;
use App\Domain\Exception\HolderNotFoundException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\DocumentResource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * @implements ProcessorInterface<mixed, DocumentResource>
 */
final readonly class DocumentProcessor implements ProcessorInterface
{
    /**
     * @param string       $maxUploadSize    Symfony size notation ("10M", "512k"), see DOCUMENT_MAX_SIZE
     * @param list<string> $allowedMimeTypes MIME types detected from the file content, see DOCUMENT_MIME_TYPES
     */
    public function __construct(
        private StoreDocumentUseCase $useCase,
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
        private SymfonyValidatorInterface $symfonyValidator,
        private string $maxUploadSize,
        private array $allowedMimeTypes,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DocumentResource
    {
        $request = $this->requestStack->getCurrentRequest();

        $input = new DocumentResource();
        $file = $request?->files->get('file');
        $input->file = $file instanceof UploadedFile ? $file : null;
        $holderId = $request?->request->get('holderId');
        $input->holderId = \is_string($holderId) ? $holderId : null;

        $this->validator->validate($input);

        if (null === $input->file || null === $input->holderId) {
            throw new UnprocessableEntityHttpException('Missing required document parameters.');
        }

        $this->assertUploadIsAcceptable($input->file);

        try {
            $document = $this->useCase->execute(new StoreDocumentCommand(
                content: $input->file->getContent(),
                holderId: $input->holderId,
            ));
        } catch (HolderNotFoundException|HolderNotActiveException|\InvalidArgumentException $e) {
            // An unknown or non-active holder makes the request unprocessable: the resource
            // being created is the document, so this is not a 404 on the request URI.
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return DocumentResource::fromEntity($document);
    }

    /**
     * Size and type limits are deployment settings (environment variables), so
     * they are checked here with runtime constraints rather than attributes.
     * The MIME type is sniffed from the content, never taken from the client.
     *
     * @throws ValidationException 422 with a "file" violation
     */
    private function assertUploadIsAcceptable(UploadedFile $file): void
    {
        $violations = $this->symfonyValidator
            ->startContext()
            ->atPath('file')
            ->validate($file, new Assert\File(
                maxSize: $this->maxUploadSize,
                mimeTypes: $this->allowedMimeTypes,
                maxSizeMessage: 'The file is too large ({{ size }} {{ suffix }}): the maximum is {{ limit }} {{ suffix }}.',
                mimeTypesMessage: 'The file type {{ type }} is not accepted. Allowed types: {{ types }}.',
            ))
            ->getViolations();

        if (0 < \count($violations)) {
            throw new ValidationException($violations);
        }
    }
}
