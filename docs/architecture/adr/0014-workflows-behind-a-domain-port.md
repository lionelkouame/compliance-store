# ADR 0014: Workflows Declared in Configuration, Driven Through a Domain Port

* **Status**: Accepted
* **Date**: 2026-11-21
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store

---

## Context

Upcoming features (bulk import of `.zip` / `.rar` archives, per-file processing with optional
prompts) move documents through several states: received, processing, processed, failed...

Until now, each lifecycle (`HolderStatus`, `StorageSpaceStatus`) is coded by hand in its entity.
That stays fine for two or three states, but multi-step processing needs a declarative,
auditable state machine. `symfony/workflow` provides one — but the Domain must not depend on
Symfony (Deptrac rule `Domain: []`) and use cases must not either (`Application: [Domain]`).

## Decision

### 1. `symfony/workflow` is installed, its workflows are declared in configuration

Every workflow lives in `config/packages/workflow.yaml`, as a `state_machine` unless several
places must be active at the same time. The file documents the conventions below.

### 2. The subject stays a plain Domain entity

The `method` marking store reads and writes the current place through `get<Property>()` /
`set<Property>()`. Since Symfony 7.4 it accepts a **backed enum**, so the entity keeps a typed
Value Object (`DocumentStatus`...) and has no idea a workflow engine exists.

### 3. Use cases depend on a Domain port

```text
 Application                      Domain                         Infrastructure
 ┌─────────────────────┐   uses   ┌──────────────────────────┐   ┌───────────────────────────┐
 │ <Action>UseCase     │ ───────► │ Port/Workflow/           │ ◄─┤ Workflow/                 │
 │  engine->apply(     │          │  WorkflowEngineInterface │   │  SymfonyWorkflowEngine    │
 │   'document_...',   │          └──────────────────────────┘   │   (service locator on the │
 │   $document,        │                                         │    "workflow" tag)        │
 │   'start')          │                                         └───────────────────────────┘
 └─────────────────────┘
```

* `WorkflowEngineInterface` exposes `can()`, `apply()` and `enabledTransitions()`.
* `SymfonyWorkflowEngine` resolves the workflow by name through a service locator built from
  the `workflow` tag, so the container stays valid while no workflow is declared yet.
* The binding is declared in `config/services.yaml`, like `DomainEventPublisher`.

### 4. A refused transition is a business fact

The adapter translates `NotEnabledTransitionException` into
`App\Domain\Exception\Workflow\TransitionNotAllowedException`, an `InvalidDataException`,
hence a **422** at the HTTP boundary ([ADR 0013](0013-business-exceptions-translated-once-at-the-boundary.md)).
An undeclared workflow name or transition is a programming error and stays a `\LogicException`.

### 5. Side effects live in Infrastructure listeners

Reactions to a transition (starting a processing, notifying...) are Symfony listeners under
`src/Infrastructure/Workflow/Listener/`. The business rules deciding whether a transition is
allowed stay in the workflow definition or in the Domain, never in a listener.

## Consequences

* ✅ The Domain and Application layers stay free of Symfony; Deptrac keeps enforcing it.
* ✅ Lifecycles become declarative and can be dumped as diagrams (`bin/console workflow:dump`).
* ✅ The adapter is unit-tested with an in-memory state machine.
* ⚠️ Workflow and transition names are strings shared between configuration and use cases:
  each feature should expose them as constants next to its use cases.
* ⚠️ `HolderStatus` and `StorageSpaceStatus` keep their hand-written transitions; migrating
  them is not required and will be decided case by case.
