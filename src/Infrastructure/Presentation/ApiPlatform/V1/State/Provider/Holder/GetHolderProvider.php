<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\Holder;

use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\Operation;
use App\Application\UseCase\GetHolderByEmail\GetHolderByEmailRequest;
use App\Application\UseCase\GetHolderByEmail\GetHolderByEmailUseCase;

readonly class GetHolderProvider implements ProviderInterface
{
    public function __construct(
        private GetHolderByEmailUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $email = $uriVariables['email'] ?? null;

        $getHolderRequestUseCase = new GetHolderByEmailRequest(
            email: $email,
        );

        return $this->useCase
            ->execute($getHolderRequestUseCase);
    }
}
