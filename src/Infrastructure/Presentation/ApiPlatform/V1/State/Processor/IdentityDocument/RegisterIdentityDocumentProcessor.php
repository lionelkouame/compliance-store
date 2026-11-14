<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\IdentityDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\IdentityDocument\RegisterIdentityDocument\RegisterIdentityDocumentRequest;
use App\Application\UseCase\IdentityDocument\RegisterIdentityDocument\RegisterIdentityDocumentUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\IdentityDocument\RegisterIdentityDocumentInput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\IdentityDocument\RegisterIdentityDocumentOutput;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<RegisterIdentityDocumentInput, RegisterIdentityDocumentOutput>
 */
final readonly class RegisterIdentityDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private RegisterIdentityDocumentUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RegisterIdentityDocumentOutput
    {
        Assert::isInstanceOf($data, RegisterIdentityDocumentInput::class);

        $this->validator->validate($data);

        Assert::string($data->type);
        Assert::string($data->country);
        Assert::string($data->number);
        Assert::string($data->expiresAt);

        $useCaseResponse = $this->useCase->execute(new RegisterIdentityDocumentRequest(
            type: $data->type,
            country: $data->country,
            number: $data->number,
            expiresAt: $data->expiresAt,
        ));

        $readModel = $useCaseResponse->readModel;

        return new RegisterIdentityDocumentOutput(
            id: $readModel->id->value(),
            type: $readModel->type->value,
            country: $readModel->country->value,
            number: $readModel->number->value,
            expiresAt: (string) $readModel->expiresAt,
            createdAt: $readModel->createdAt->format(\DateTimeInterface::ATOM),
        );
    }
}
