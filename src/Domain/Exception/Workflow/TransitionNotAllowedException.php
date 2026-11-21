<?php

declare(strict_types=1);

namespace App\Domain\Exception\Workflow;

use App\Domain\Exception\InvalidDataException;

/**
 * The requested transition is not enabled from the subject's current place.
 *
 * Categorised as InvalidDataException, so it is translated to a 422 at the
 * HTTP boundary (ADR 0013). The message never contains personal data.
 */
final class TransitionNotAllowedException extends InvalidDataException
{
    public function __construct(string $workflow, string $transition)
    {
        parent::__construct(\sprintf(
            'The transition "%s" of the workflow "%s" is not allowed from the current state.',
            $transition,
            $workflow,
        ));
    }
}
