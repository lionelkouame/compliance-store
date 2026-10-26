<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateHolder;

use App\Domain\Entity\Holder;
use App\Domain\Exception\HolderAlreadyExistsException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\AuditTrailInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ValueObject\AuditAction;
use App\Domain\ValueObject\BirthDate;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\HolderId;
use App\Domain\ValueObject\HolderName;

final readonly class CreateHolderUseCase
{
    public function __construct(
        private GeneratorInterface $idGenerator,
        private HolderRepositoryInterface $holders,
        private AuditTrailInterface $auditTrail,
    ) {}

    /**
     * @throws HolderAlreadyExistsException when another holder already uses this email
     * @throws \InvalidArgumentException    when the name, the birth date or the email is invalid
     */
    public function execute(CreateHolderCommand $command): Holder
    {
        $holderId = HolderId::fromString($this->idGenerator->generate());
        $holderName = new HolderName($command->holderName);
        $birthDate = BirthDate::fromString($command->birthDate);
        $email = new Email($command->email);

        if (null !== $this->holders->findByEmail($email->value)) {
            throw HolderAlreadyExistsException::withEmail($email);
        }

        $holder = Holder::register(
            id: $holderId,
            name: $holderName,
            birthDate: $birthDate,
            email: $email,
        );

        $this->holders->add($holder);

        $this->auditTrail->record(AuditAction::HolderRegistered, $holderId->value);

        return $holder;
    }
}
