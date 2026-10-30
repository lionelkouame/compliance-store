<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\Holder;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailRequest;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailResponse;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailUseCase;

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
