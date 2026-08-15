<?php

declare(strict_types=1);

namespace App\Application\UseCase\UpdateLegalFrameworkStatus;

use App\Domain\Entity\LegalFramework;
use App\Domain\Exception\InvalidLegalFrameworkException;
use App\Domain\Port\Repository\LegalFrameworkRepositoryInterface;
use App\Domain\ValueObject\LegalFrameworkCode;

final readonly class UpdateLegalFrameworkStatusUseCase
{
    public function __construct(
        private LegalFrameworkRepositoryInterface $legalFrameworks,
    ) {}

    /**
     * @throws InvalidLegalFrameworkException if the code is unknown
     */
    public function execute(UpdateLegalFrameworkStatusCommand $command): LegalFramework
    {
        if (!LegalFrameworkCode::isValid($command->code)) {
            throw InvalidLegalFrameworkException::forCode($command->code);
        }

        $legalFramework = $this->legalFrameworks->findByCode(new LegalFrameworkCode($command->code));

        if (null === $legalFramework) {
            throw InvalidLegalFrameworkException::forCode($command->code);
        }

        if ($command->active) {
            $legalFramework->activate();
        } else {
            $legalFramework->deactivate();
        }

        $this->legalFrameworks->update($legalFramework);

        return $legalFramework;
    }
}
