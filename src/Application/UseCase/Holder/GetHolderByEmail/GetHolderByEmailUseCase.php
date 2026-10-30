<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\GetHolderByEmail;

use App\Domain\Port\Repository\HolderRepositoryInterface;

readonly class GetHolderByEmailUseCase
{
    public function __construct(
       private HolderRepositoryInterface $repository
    ){}

    public function execute(GetHolderByEmailRequest $holderEmailRequest): ?GetHolderByEmailResponse
    {
        $holder = $this->repository
            ->findByEmail($holderEmailRequest->email);

        if (null === $holder) {
            return null;
        }

        return new GetHolderByEmailResponse($holder);
    }
}
