<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\V1;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\UpdateLegalFrameworkStatus\UpdateLegalFrameworkStatusCommand;
use App\Application\UseCase\UpdateLegalFrameworkStatus\UpdateLegalFrameworkStatusUseCase;
use App\Infrastructure\ApiPlatform\Resource\V1\LegalFrameworkResource;

/**
 * @implements ProcessorInterface<LegalFrameworkResource, LegalFrameworkResource>
 */
final readonly class LegalFrameworkStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateLegalFrameworkStatusUseCase $useCase,
    ) {}

    /**
     * @param LegalFrameworkResource $data
     * @param array{code?: string} $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LegalFrameworkResource
    {
        $legalFramework = $this->useCase->execute(new UpdateLegalFrameworkStatusCommand(
            code: $uriVariables['code'] ?? '',
            active: $data->active,
        ));

        return LegalFrameworkResource::fromEntity($legalFramework);
    }
}
