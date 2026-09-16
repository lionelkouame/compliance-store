---
inclusion: always
---

# Executing spec tasks

This protocol applies to every task of a `.kiro/specs/<feature>/tasks.md`, whoever runs it.

## Before starting a task

1. Read the requirements the task cites (`_Requirements: x.y_`) and the ADRs it cites
   (`_ADR: nnnn_`).
2. Check that every earlier task it depends on is ticked. Tasks are ordered by technical
   dependency: Domain → Application → Infrastructure → API → API tests.
3. Make sure the Docker stack is running (`make up`): the gate cannot run without it.

## Doing the task

1. **Write the test first**, citing the requirement it covers (see `testing.md`). Run it
   and check it fails for the right reason.
2. Implement the smallest change that makes it pass, within the files the task names.
3. Run **`make test`**. It chains deptrac, PHPStan (level max) and PHPUnit.

## Finishing the task

A task is done only when **`make test` is green**. Then:

- tick its box in `tasks.md`;
- make **one commit** for the task (see `git-conventions.md`), `tasks.md` included;
- move to the next task.

If `make test` is red, the task is not done: fix it, or stop and explain what blocks.
Never tick a box, commit, or start the next task on a red gate.

## When the spec is wrong or silent

Do not diverge silently. If a task contradicts an ADR or a requirement, or cannot be
done as written, stop and say so: the fix belongs in the spec or in a new ADR, not in an
improvised implementation.
