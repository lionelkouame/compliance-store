<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State\Processor\Holder;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailRequest;
use App\Application\UseCase\Holder\GetHolderByEmail\GetHolderByEmailUseCase;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\HolderOutput;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\Holder\SearchHolderInput;
use Webmozart\Assert\Assert;

/**
 * Recherche d'un titulaire par email. C'est un POST et non un GET : le chemin
 * comme la query string sont journalisés, le corps de la requête ne l'est pas.
 *
 * @implements ProcessorInterface<SearchHolderInput, HolderOutput>
 */
final readonly class SearchHolderProcessor implements ProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private GetHolderByEmailUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HolderOutput
    {
        Assert::isInstanceOf($data, SearchHolderInput::class);

        $this->validator->validate($data);

        Assert::string($data->email);

        $useCaseResponse = $this->useCase->execute(new GetHolderByEmailRequest(
            email: $data->email,
        ));

        return HolderOutput::fromReadModel($useCaseResponse->readModel);
    }
}
