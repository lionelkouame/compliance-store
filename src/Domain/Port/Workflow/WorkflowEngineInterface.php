<?php

declare(strict_types=1);

namespace App\Domain\Port\Workflow;

use App\Domain\Exception\Workflow\TransitionNotAllowedException;

/**
 * Port the application uses to move a subject through a workflow
 * (state machine) declared in configuration.
 *
 * Kept in the domain so use cases never depend on symfony/workflow directly
 * (ADR 0014). The infrastructure provides the adapter.
 *
 * The subject is a plain domain entity: it exposes its current place through
 * a getter/setter pair typed with a backed enum, nothing more.
 */
interface WorkflowEngineInterface
{
    /**
     * Whether the transition can be applied to the subject in its current place.
     */
    public function can(string $workflow, object $subject, string $transition): bool;

    /**
     * Applies the transition and updates the subject's place.
     *
     * @throws TransitionNotAllowedException when the transition is not enabled
     *                                       from the subject's current place
     */
    public function apply(string $workflow, object $subject, string $transition): void;

    /**
     * Names of the transitions that can be applied from the subject's current place.
     *
     * @return list<string>
     */
    public function enabledTransitions(string $workflow, object $subject): array;
}
