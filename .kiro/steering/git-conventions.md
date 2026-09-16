---
inclusion: always
---

# Git and GitHub conventions

## Commits — Conventional Commits

```text
<type>(<optional scope>): <imperative description, in English>
```

| Type | Usage |
| :--- | :--- |
| `feat` | new functional capability |
| `fix` | bug fix |
| `refactor` | restructuring without behaviour change |
| `test` | tests only |
| `docs` | documentation, ADR, steering |
| `chore` | dependencies, tooling, CI |

Reference the issue when there is one: `feat: #40 prevent domain leak through StorageKey`.

**One commit = one task of `tasks.md` = one green gate.** Never group two tasks, never
commit on a red gate.

The message body explains **why**, not what — the diff already says what.

> This repository is **public**. A commit message never cites non-public planning or
> strategic trade-offs. It cites a public issue or an ADR.

## Branches

```text
features/<issue-number>-<slug>     features/34-us-refacto-infrastructure
fix/<slug>                         fix/document-read-empty-payload
```

Never commit directly on `main`.

## Pull requests

The `.github/PULL_REQUEST_TEMPLATE.md` template is mandatory. In particular:

- `Closes #<issue>` filled in;
- "How was this tested?": **paste the output of `make test`**, do not describe it;
- "No unrelated changes bundled in": one PR = one scope.

CI (`.github/workflows/ci.yaml`) replays tests and lint. A red PR is not up for discussion.

## Issues

Two templates: `epic.yml` (business goal, no implementation detail) and `user_story.yml`
(one deliverable capability). User stories are attached to their epic through
**Add sub-issue**.

A public issue is written to be **self-contained**: understandable by an external
contributor with no access to anything outside this repository.

## Git hooks

```bash
make setup-hooks     # enables .githooks — once per clone or worktree
```

## Forbidden without human approval

```text
✗ merging a PR           ✗ pushing to main          ✗ push --force
✗ tag or release         ✗ deleting a remote branch
```
