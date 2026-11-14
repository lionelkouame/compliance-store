<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\Holder;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailRequest;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\GetHolderEmailOutput;

/**
 * @implements ProviderInterface<GetHolderEmailOutput>
 */
final readonly class GetHolderProvider implements ProviderInterface
{
    public function __construct(
        private GetHolderByEmailUseCase $useCase,
    ) {}

    /**
     * @param array{email?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GetHolderEmailOutput
    {
        $readModel = $this->useCase->execute(new GetHolderByEmailRequest(
            email: $uriVariables['email'] ?? '',
        ))->readModel;

        return new GetHolderEmailOutput(
            holderId: $readModel->id->value(),
            holderName: $readModel->name->value,
            birthDate: (string) $readModel->birthDate,
            status: $readModel->status->value,
            email: $readModel->email->value,
            createdAt: $readModel->createdAt->format(\DateTimeInterface::ATOM),
            consentCollectedAt: $readModel->consentCollectedAt?->format(\DateTimeInterface::ATOM),
            erasureRequestedAt: $readModel->erasureRequestedAt?->format(\DateTimeInterface::ATOM),
            anonymizedAt: $readModel->anonymizedAt?->format(\DateTimeInterface::ATOM),
        );
    }
}
