<?php

namespace App\Application\UseCase\CreateHolder;


use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;

final readonly class CreateHolderUseCase
{
    public function __construct(
        private GeneratorInterface $generatorUuid,
        private HolderRepositoryInterface $holderRepository
    ){}

    public function execute(CreateHolderCommand $command) : Holder
    {
        $value = $this->generatorUuid->generate();
        $holderId = HolderId::fromString($value);
        $birthDate = BirthDate::fromString($command->birthDate);
        $holderName = new HolderName($command->holderName);
        $email = new Email($command->email);

        if (null !== $this->holderRepository->findByEmail($email->value)) {
            throw new HolderAlreadyExistsException($holderId);
        }

        $holder = Holder::register(
            $holderId,
            $holderName,
            $birthDate,
            $email
        );

        $this->holderRepository->add($holder);

        return  $holder;
    }
}
