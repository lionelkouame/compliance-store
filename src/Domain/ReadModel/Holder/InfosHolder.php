<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\Holder;

use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Generic\BirthDate;
use App\Domain\ValueObject\Holder\HolderId;
use App\Domain\ValueObject\Holder\HolderName;
use App\Domain\ValueObject\Holder\HolderStatus;

final readonly class InfosHolder
{
    public function __construct(
        public HolderId $id,
        public HolderName $name,
        public BirthDate $birthDate,
        public HolderStatus $status,
        public Email $email,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $consentCollectedAt,
        public ?\DateTimeImmutable $erasureRequestedAt,
        public ?\DateTimeImmutable $anonymizedAt,
    ) {}
}
