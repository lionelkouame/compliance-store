<?php

declare(strict_types=1);

namespace App\Domain\ValueObject\Holder;

use App\Domain\ValueObject\IdentifierInterface;
use App\Domain\ValueObject\IdentifierTrait;

final readonly class HolderId implements IdentifierInterface
{
    use IdentifierTrait;
}
