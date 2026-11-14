<?php

declare(strict_types=1);

namespace App\Application\UseCase\IdentityDocument\RegisterIdentityDocument;

use App\Application\UseCase\UseCaseResponseInterface;
use App\Domain\ReadModel\IdentityDocument\InfosIdentityDocument;

final readonly class RegisterIdentityDocumentResponse implements UseCaseResponseInterface
{
    public function __construct(
        public InfosIdentityDocument $readModel,
    ) {}
}
