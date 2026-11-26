<?php

declare(strict_types=1);

namespace App\Domain\ReadModel\Holder;

use App\Domain\ValueObject\Shared\Identifier\HolderId;

readonly class CreateHolderRead
{
    public function __construct(
        public HolderId $id,
    )
    {}
}
