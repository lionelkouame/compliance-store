<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Holder\RequestHolderErasure\RequestHolderErasureRequest;
use App\Application\UseCase\Holder\RequestHolderErasure\RequestHolderErasureUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\HolderOutput;

/**
 * Enregistre une demande d'effacement. L'opération n'a pas de corps : seul
 * l'identifiant de l'URL compte.
 *
 * @implements ProcessorInterface<null, HolderOutput>
 */
final readonly class RequestHolderErasureProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestHolderErasureUseCase $useCase,
    ) {}

    /**
     * @param array{id?: string} $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HolderOutput
    {
        $useCaseResponse = $this->useCase->execute(new RequestHolderErasureRequest(
            id: $uriVariables['id'] ?? '',
        ));

        return HolderOutput::fromReadModel($useCaseResponse->readModel);
    }
}
