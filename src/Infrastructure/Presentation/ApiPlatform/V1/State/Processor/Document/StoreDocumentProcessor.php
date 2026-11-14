<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\Document\StoreDocument\StoreDocumentRequest;
use App\Application\UseCase\Document\StoreDocument\StoreDocumentUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document\StoreDocumentInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document\StoreDocumentOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<mixed, StoreDocumentOutput>
 */
final readonly class StoreDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private StoreDocumentUseCase $useCase,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreDocumentOutput
    {
        Assert::isInstanceOf($data, StoreDocumentInput::class);

        $this->validator->validate($data);
        Assert::notNull($data->file);

        $useCaseRequest = new StoreDocumentRequest(
            $data->file->getContent(),
        );

        $useCaseResponse = $this->useCase->execute($useCaseRequest);

        return new StoreDocumentOutput(
            id: $useCaseResponse->id,
            holderId: $useCaseResponse->holderId,
            fileHash: $useCaseResponse->fileHash,
            createdAt: $useCaseResponse->createdAt,
        );
    }
}
