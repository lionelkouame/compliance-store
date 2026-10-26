<?php

declare(strict_types=1);

namespace App\Domain\Port\Service;

/**
 * Domain port for generating a unique identifier as a raw string.
 *
 * Unlike the typed generators ({@see StorageSpaceIdGeneratorInterface},
 * {@see DocumentIdGeneratorInterface}), this port is shared by every
 * aggregate: the use case wraps the returned string in the proper
 * identifier value object.
 */
interface GeneratorInterface
{
    public function generate(): string;
}
