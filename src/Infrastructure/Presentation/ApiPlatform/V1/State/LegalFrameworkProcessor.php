<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\CreateLegalFramework\CreateLegalFrameworkCommand;
use App\Application\UseCase\CreateLegalFramework\CreateLegalFrameworkUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\LegalFrameworkResource;

/**
 * @implements ProcessorInterface<LegalFrameworkResource, LegalFrameworkResource>
 */
final readonly class LegalFrameworkProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateLegalFrameworkUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LegalFrameworkResource
    {
        $legalFramework = $this->useCase->execute(new CreateLegalFrameworkCommand(
            code: (string) $data->code,
            name: (string) $data->name,
            officialReference: (string) $data->officialReference,
            regulatoryAuthority: (string) $data->regulatoryAuthority,
            jurisdictionCode: (string) $data->jurisdictionCode,
            active: $data->active,
        ));

        return LegalFrameworkResource::fromEntity($legalFramework);
    }
}
