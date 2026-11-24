<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Provider\Holder;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Holder\GetHolder\GetHolderRequest;
use App\Application\UseCase\Holder\GetHolder\GetHolderUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\HolderOutput;

/**
 * @implements ProviderInterface<HolderOutput>
 */
final readonly class GetHolderProvider implements ProviderInterface
{
    public function __construct(
        private GetHolderUseCase $useCase,
    ) {}

    /**
     * @param array{id?: string} $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HolderOutput
    {
        $useCaseResponse = $this->useCase->execute(new GetHolderRequest(
            id: $uriVariables['id'] ?? '',
        ));

        return HolderOutput::fromReadModel($useCaseResponse->readModel);
    }
}
