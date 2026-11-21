<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Workflow;

use App\Domain\Exception\Workflow\TransitionNotAllowedException;
use App\Infrastructure\Workflow\SymfonyWorkflowEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

final class SymfonyWorkflowEngineTest extends TestCase
{
    private SymfonyWorkflowEngine $engine;

    protected function setUp(): void
    {
        $definition = (new DefinitionBuilder())
            ->addPlaces(['received', 'processing', 'processed'])
            ->setInitialPlaces('received')
            ->addTransition(new Transition('start', 'received', 'processing'))
            ->addTransition(new Transition('complete', 'processing', 'processed'))
            ->build();

        $stateMachine = new StateMachine($definition, new MethodMarkingStore(true, 'status'), name: 'test_processing');

        $this->engine = new SymfonyWorkflowEngine(new ServiceLocator([
            'test_processing' => static fn (): StateMachine => $stateMachine,
        ]));
    }

    public function testApplyMovesTheSubjectAndStoresTheBackedEnum(): void
    {
        $subject = new WorkflowSubject();

        $this->engine->apply('test_processing', $subject, 'start');

        self::assertSame(WorkflowSubjectStatus::Processing, $subject->getStatus());
    }

    public function testCanAndEnabledTransitionsReflectTheCurrentPlace(): void
    {
        $subject = new WorkflowSubject();

        self::assertTrue($this->engine->can('test_processing', $subject, 'start'));
        self::assertFalse($this->engine->can('test_processing', $subject, 'complete'));
        self::assertSame(['start'], $this->engine->enabledTransitions('test_processing', $subject));
    }

    public function testApplyTranslatesARefusedTransitionIntoADomainException(): void
    {
        $this->expectException(TransitionNotAllowedException::class);

        $this->engine->apply('test_processing', new WorkflowSubject(), 'complete');
    }

    public function testAnUndeclaredWorkflowIsAProgrammingError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No workflow named "unknown"');

        $this->engine->can('unknown', new WorkflowSubject(), 'start');
    }
}

enum WorkflowSubjectStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Processed = 'processed';
}

/** Plain subject with no dependency on symfony/workflow, like a Domain entity. */
final class WorkflowSubject
{
    private WorkflowSubjectStatus $status = WorkflowSubjectStatus::Received;

    public function getStatus(): WorkflowSubjectStatus
    {
        return $this->status;
    }

    public function setStatus(WorkflowSubjectStatus $status): void
    {
        $this->status = $status;
    }
}
