<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Holder\AnonymizeHolder\AnonymizeHolderRequest;
use App\Application\UseCase\Holder\AnonymizeHolder\AnonymizeHolderUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\HolderOutput;

/**
 * Exécute l'effacement (anonymisation + suppression des documents). L'opération n'a pas de corps : seul
 * l'identifiant de l'URL compte.
 *
 * @implements ProcessorInterface<null, HolderOutput>
 */
final readonly class AnonymizeHolderProcessor implements ProcessorInterface
{
    public function __construct(
        private AnonymizeHolderUseCase $useCase,
    ) {}

    /**
     * @param array{id?: string} $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HolderOutput
    {
        $useCaseResponse = $this->useCase->execute(new AnonymizeHolderRequest(
            id: $uriVariables['id'] ?? '',
        ));

        return HolderOutput::fromReadModel($useCaseResponse->readModel);
    }
}
