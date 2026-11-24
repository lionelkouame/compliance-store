<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\CreateHolder;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\Holder;
use App\Domain\Event\Holder\HolderCreated;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\Port\Event\DomainEventPublisher;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ReadModel\Holder\CreateHolderRead;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;

/**
 * @implements UseCaseInterface<CreateHolderRequest, CreateHolderResponse>
 */
final readonly class CreateHolderUseCase implements UseCaseInterface
{
    public function __construct(
        private GeneratorInterface        $generatorUuid,
        private HolderRepositoryInterface $holderRepository,
        private DomainEventPublisher      $domainEventPublisher,
    ){}

    /**
     * @throws HolderAlreadyExistsException when the email is already used
     * @throws InvalidValueException when a field breaks a value object invariant
     */
    public function execute(UseCaseRequestInterface $registerIdentityDocumentRequest) : CreateHolderResponse
    {
        $holderId = HolderId::make($this->generatorUuid->generate());
        $birthDate = BirthDate::make($registerIdentityDocumentRequest->birthDate);
        $holderName = HolderName::make($registerIdentityDocumentRequest->holderName);
        $email = Email::make($registerIdentityDocumentRequest->email);

        if (null !== $this->holderRepository->findByEmail($email)) {
            throw new HolderAlreadyExistsException();
        }

        $holder = Holder::create(
            $holderId,
            $holderName,
            $birthDate,
            $email
        );

        $this->holderRepository->add($holder);

        $this->domainEventPublisher->publish(HolderCreated::fromHolder($holder));

        return new CreateHolderResponse(
            new CreateHolderRead($holderId)
        );
    }
}
