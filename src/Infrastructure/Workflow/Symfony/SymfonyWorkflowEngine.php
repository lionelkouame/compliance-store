<?php

declare(strict_types=1);

namespace App\Infrastructure\Workflow;

use App\Domain\Exception\Workflow\TransitionNotAllowedException;
use App\Domain\Port\Workflow\WorkflowEngineInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Adapter wiring the domain {@see WorkflowEngineInterface} port onto
 * symfony/workflow.
 *
 * Workflows are declared in config/packages/workflow.yaml and resolved by
 * name through a service locator built from the "workflow" tag, so the
 * adapter keeps working while no workflow is declared yet.
 */
final readonly class SymfonyWorkflowEngine implements WorkflowEngineInterface
{
    public function __construct(
        #[AutowireLocator('workflow', indexAttribute: 'name')]
        private ContainerInterface $workflows,
    ) {}

    public function can(string $workflow, object $subject, string $transition): bool
    {
        return $this->get($workflow)->can($subject, $transition);
    }

    public function apply(string $workflow, object $subject, string $transition): void
    {
        try {
            $this->get($workflow)->apply($subject, $transition);
        } catch (NotEnabledTransitionException) {
            throw new TransitionNotAllowedException($workflow, $transition);
        }
    }

    public function enabledTransitions(string $workflow, object $subject): array
    {
        return array_values(array_map(
            static fn (Transition $transition): string => $transition->getName(),
            $this->get($workflow)->getEnabledTransitions($subject),
        ));
    }

    private function get(string $workflow): WorkflowInterface
    {
        if (!$this->workflows->has($workflow)) {
            throw new \LogicException(\sprintf('No workflow named "%s" is declared in config/packages/workflow.yaml.', $workflow));
        }

        $service = $this->workflows->get($workflow);
        \assert($service instanceof WorkflowInterface);

        return $service;
    }
}
