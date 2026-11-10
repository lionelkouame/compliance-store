<?php

declare(strict_types=1);

namespace App\Application\UseCase\Holder\CreateHolder;

use App\Application\UseCase\UseCaseInterface;
use App\Application\UseCase\UseCaseRequestInterface;
use App\Domain\Entity\Holder;
use App\Domain\Exception\Holder\HolderAlreadyExistsException;
use App\Domain\Port\Repository\HolderRepositoryInterface;
use App\Domain\Port\Service\GeneratorInterface;
use App\Domain\ReadModel\Holder\CreateHolderRead;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use Webmozart\Assert\Assert;

final readonly class CreateHolderUseCase implements UseCaseInterface
{
    public function __construct(
        private GeneratorInterface $generatorUuid,
        private HolderRepositoryInterface $holderRepository
    ){}

    public function execute(UseCaseRequestInterface $request) : CreateHolderResponse
    {
        Assert::isInstanceOf($request, CreateHolderRequest::class);

        $holderId = HolderId::make($this->generatorUuid->generate());
        $birthDate = BirthDate::make($request->birthDate);
        $holderName = HolderName::make($request->holderName);
        $email = Email::make($request->email);

        if (null !== $this->holderRepository->findByEmail($email->value)) {
            throw new HolderAlreadyExistsException();
        }

        $holder = Holder::create(
            $holderId,
            $holderName,
            $birthDate,
            $email
        );

        $this->holderRepository->add($holder);

        return new CreateHolderResponse(
            new CreateHolderRead($holderId)
        );
    }
}
