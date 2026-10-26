<?php

declare(strict_types=1);

namespace App\Infrastructure\Presentation\ApiPlatform\V1\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\RequestHolderErasure\RequestHolderErasureUseCase;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Exception\InvalidHolderStatusTransitionException;
use App\Infrastructure\Presentation\ApiPlatform\V1\Resource\HolderResource;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<mixed, HolderResource>
 */
final readonly class RequestHolderErasureProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestHolderErasureUseCase $useCase,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HolderResource
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
        } catch (InvalidHolderStatusTransitionException $e) {
            // The request is well-formed but the holder is not in a state that allows it.
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return HolderResource::fromEntity($holder);
    }
}
