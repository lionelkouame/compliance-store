<?php

declare(strict_types=1);

namespace App\Domain\Port\Repository;

use App\Domain\Entity\Holder;
use App\Domain\ValueObject\Document\Email;
use App\Domain\ValueObject\Holder\HolderId;

interface HolderRepositoryInterface
{
    public function findById(HolderId $id): ?Holder;

    public function findByEmail(Email $email): ?Holder;

    public function add(Holder $holder): void;
}
