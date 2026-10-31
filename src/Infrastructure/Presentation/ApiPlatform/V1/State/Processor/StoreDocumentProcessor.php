<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\StoreDocument\StoreDocumentRequest;
use App\Application\UseCase\StoreDocument\StoreDocumentUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document\StoreDocumentInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document\StoreDocumentOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<mixed, StoreDocumentOutput>
 */
final readonly class StoreDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private StoreDocumentUseCase $useCase,
        private RequestStack $requestStack,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreDocumentOutput
    {
        // With `deserialize: false`, API Platform does not populate $data.
        // The input is built manually from the raw multipart request.
        $request = $this->requestStack->getCurrentRequest();

        $input = new StoreDocumentInput();
        $file = $request?->files->get('file');
        $input->file = $file instanceof UploadedFile ? $file : null;

        // Triggers the #[Assert\NotNull] / #[Assert\File] constraints on the input.
        $this->validator->validate($input);

        try {
            $response = $this->useCase->execute(new StoreDocumentRequest(
                content: $input->file->getContent(),
            ));
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return new StoreDocumentOutput(
            id: $response->id,
            holderId: $response->holderId,
            fileHash: $response->fileHash,
            // The wrapped data key is raw binary (Libsodium); base64-encode it
            // so it can be safely serialized as a JSON (UTF-8) string.
            wrappedDataKey: base64_encode($response->wrappedDataKey),
            createdAt: $response->createdAt,
        );
    }
}
