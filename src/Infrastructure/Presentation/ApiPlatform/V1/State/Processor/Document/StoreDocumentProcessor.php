<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Document;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\Document\StoreDocument\StoreDocumentRequest;
use App\Application\UseCase\Document\StoreDocument\StoreDocumentUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document\StoreDocumentInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Document\StoreDocumentOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<StoreDocumentInput, StoreDocumentOutput>
 */
final readonly class StoreDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private StoreDocumentUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreDocumentOutput
    {
        Assert::isInstanceOf($data, StoreDocumentInput::class);

        $this->validator->validate($data);

        Assert::string($data->holderId);
        Assert::notNull($data->file);

        $useCaseResponse = $this->useCase->execute(new StoreDocumentRequest(
            holderId: $data->holderId,
            content: $data->file->getContent(),
        ));

        $readModel = $useCaseResponse->readModel;

        return new StoreDocumentOutput(
            id: $readModel->id->value(),
            holderId: $readModel->holderId->value(),
            fileHash: $readModel->fileHash->value(),
            createdAt: $readModel->createdAt->format(\DateTimeInterface::ATOM),
        );
    }
}
