<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetHolder;

use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderNotFoundException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\IdValidatorInterface;
use App\Domain\ValueObject\HolderId;

final readonly class GetHolderUseCase
{
    public function __construct(
        private HolderRepositoryInterface $holders,
        private IdValidatorInterface $idValidator,
    ) {}

    /**
     * @throws HolderNotFoundException
     * @throws \InvalidArgumentException when the id is not a valid UUID
     */
    public function execute(string $id): Holder
    {
        $holderId = HolderId::fromString($id, $this->idValidator);

        $holder = $this->holders->findById($holderId);

        if (null === $holder) {
            throw new HolderNotFoundException($holderId);
        }

        return $holder;
    }
}
