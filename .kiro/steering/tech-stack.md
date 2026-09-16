---
inclusion: always
---

# Tech stack

| Component | Version | Note |
| :--- | :--- | :--- |
| PHP | `>= 8.4` | platform pinned to `8.4.1` in `composer.json` |
| Symfony | `8.1.*` | strict constraint — do not widen without an ADR |
| API Platform | `^4.3` | REST exposure, State Providers/Processors |
| Doctrine ORM | `^3.6.7, <3.6.8` | **deliberate upper bound**, do not lift |
| PostgreSQL | 16 | |
| Object storage | MinIO via `league/flysystem-aws-s3-v3` | |
| Encryption | `ext-sodium` | envelope encryption, ADR 0002 |
| Server | FrankenPHP + Caddy | Docker Compose |

Autoload: `App\` → `src/`, `App\Tests\` → `tests/` (PSR-4).

---

## Commands — always go through the Makefile

```bash
make start      # build + up
make test       # deptrac → phpstan → phpunit   ← THE gate
make deptrac    # Clean Architecture layer rules
make phpstan    # static analysis, level max
make sf c=...   # Symfony console inside the container
make sh         # shell inside the php container
```

**`make test` chains the three checks.** It is the only command that allows saying a task
is done. Never run `phpunit` alone and conclude it is green.

Several worktrees in parallel: every published port is configurable through `.env.local`
(not versioned, loaded by the Makefile) — see `docs/docker-infra/parallel-worktrees.md`.

Commands run **inside the container** (`docker compose exec php`). Never invoke `php`,
`composer` or `vendor/bin/*` directly from the host.

---

## Layers and dependency rule

Enforced mechanically by `deptrac.yaml` — this is not a convention, it is a test:

```bash
Domain          → nothing   (pure PHP: no Symfony, no Doctrine, no API Platform)
Application     → Domain
Infrastructure  → Domain, Application, Symfony, Doctrine, ApiPlatform
```

```text
src/
├── Domain/          Entity · ValueObject · Event · Exception
│   └── Port/        Gateway · Repository · Service · Clock · Event · Notification
├── Application/     UseCase/<UseCaseName>/ · Dto/
└── Infrastructure/  Gateway · Storage · Persistence/Doctrine · Service
    └── Presentation/ApiPlatform/V1/{Resource,State}
```

One use case = **one folder** `Application/UseCase/<Name>/` holding its command and handler.

---

## Non-negotiable design rules

- **No primitive in the Domain's public surface** (ADR 0004). Every business scalar is
  carried by an immutable Value Object that validates its invariant in its constructor.
  A list is a collection Value Object (`IteratorAggregate` + `Countable`), not an `array`.
- Only **application DTOs** (`Application/UseCase/*/*Command`), which cross HTTP
  (de)serialisation, stay primitive. The use case builds the Value Objects before calling
  the Domain.
- **API Platform is decoupled from the Domain** (ADR 0003): `#[ApiResource]` classes are
  infrastructure DTOs, never entities. Data flows through State Providers / Processors.
- **Double validation lock** (ADR 0005): declarative constraints on the DTO **and** the
  invariant in the Value Object. Both, not one or the other.
- Doctrine mapping is **XML** (`Infrastructure/Persistence/Doctrine/Mapping/`), not
  attributes — the Domain must not know Doctrine.

---

## Before adding a dependency

A dependency in `Domain/` is almost always a design mistake: `deptrac` will reject it.
A new Composer dependency is justified in the PR, and falls under an ADR when it enters a
structural decision.
