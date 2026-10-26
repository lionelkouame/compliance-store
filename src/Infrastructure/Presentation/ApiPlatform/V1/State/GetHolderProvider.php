<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\GetHolder\GetHolderUseCase;
use App\Domain\Exception\HolderNotFoundException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\HolderResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<HolderResource>
 */
final readonly class GetHolderProvider implements ProviderInterface
{
    public function __construct(
        private GetHolderUseCase $useCase,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HolderResource
    {
        $id = $uriVariables['id'] ?? null;

        if (!\is_string($id)) {
            throw new NotFoundHttpException('Invalid holder id.');
        }

        try {
            $holder = $this->useCase->execute($id);
        } catch (HolderNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Invalid holder id.', $e);
        }

        return HolderResource::fromEntity($holder);
    }
}
