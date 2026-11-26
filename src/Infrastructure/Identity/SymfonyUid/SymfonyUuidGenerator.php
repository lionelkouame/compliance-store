<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\SymfonyUid;

use App\Domain\Port\Service\GeneratorInterface;
use Symfony\Component\Uid\Uuid;

class SymfonyUuidGenerator implements GeneratorInterface
{

    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
