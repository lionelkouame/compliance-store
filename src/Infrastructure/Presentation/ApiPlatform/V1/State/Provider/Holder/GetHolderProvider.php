<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\Holder;

use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\Operation;
use App\Application\UseCase\GetHolderByEmail\GetHolderByEmailRequest;
use App\Application\UseCase\GetHolderByEmail\GetHolderByEmailResponse;
use App\Application\UseCase\GetHolderByEmail\GetHolderByEmailUseCase;

/**
 * @implements ProviderInterface<GetHolderByEmailResponse>
 */
readonly class GetHolderProvider implements ProviderInterface
{
    public function __construct(
        private GetHolderByEmailUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?GetHolderByEmailResponse
    {
        $email = $uriVariables['email'] ?? null;

        if (!\is_string($email)) {
            return null;
        }

        $getHolderRequestUseCase = new GetHolderByEmailRequest(
            email: $email,
        );

        return $this->useCase
            ->execute($getHolderRequestUseCase);
    }
}
