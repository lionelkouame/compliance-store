<?php

declare(strict_types=1);

namespace App\Application\UseCase\StorageSpace\ListStorageSpaces;

use App\Application\UseCase\UseCaseRequestInterface;

/**
 * No criteria yet: kept so that filters / pagination can be added
 * without changing the use case signature.
 */
final readonly class ListStorageSpacesRequest implements UseCaseRequestInterface
{
}
