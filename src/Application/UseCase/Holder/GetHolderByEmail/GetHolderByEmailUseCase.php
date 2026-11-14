<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolderByEmail;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Exception\Holder\HolderNotFoundException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\ReadModel\Holder\InfosHolder;

/**
 * @implements UseCaseInterface<GetHolderByEmailRequest, GetHolderByEmailResponse>
 */
final readonly class GetHolderByEmailUseCase implements UseCaseInterface
{
    public function __construct(
        private HolderRepositoryInterface $holderRepository,
    ) {}

    public function execute(UseCaseRequestInterface $request): GetHolderByEmailResponse
    {
        $holder = $this->holderRepository->findByEmail($request->email);

        if (null === $holder) {
            throw new HolderNotFoundException();
        }

        return new GetHolderByEmailResponse(new InfosHolder(
            id: $holder->id(),
            name: $holder->name(),
            birthDate: $holder->birthDate(),
            status: $holder->status(),
            email: $holder->email(),
            createdAt: $holder->createdAt(),
            consentCollectedAt: $holder->consentCollectedAt(),
            erasureRequestedAt: $holder->erasureRequestedAt(),
            anonymizedAt: $holder->anonymizedAt(),
        ));
    }
}
