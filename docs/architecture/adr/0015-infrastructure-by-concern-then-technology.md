# ADR 0015: Infrastructure Organised by Concern, Then by Technology

* **Status**: Accepted
* **Date**: 2026-11-26
* **Author**: Lionel Kouamé
* **Project**: Compliance Store
* **Supplements**: ADR 0008

---

## Context

ADR 0008 introduced `Persistence/Doctrine` and `Presentation/ApiPlatform`: a technical concern
first, the technology second. The rest of `src/Infrastructure` did not follow that rule:

```text
Infrastructure/
├─ Event/            # SymfonyDomainEventDispatcher, Listener/ (technology only in class names)
├─ Gateway/          # SodiumCipherGateway ("Gateway" is a pattern, not a concern)
├─ Service/          # UUID generators/validator + an unused Comparator enum (catch-all)
├─ Storage/          # MinioStorageGateway (actually talks to Flysystem), StorageKey
└─ Workflow/         # SymfonyWorkflowEngine
```

A technology-first layout (`Infrastructure/Symfony`, `Infrastructure/Doctrine`,
`Infrastructure/ApiPlatform`...) was considered and rejected: `Symfony` would gather events,
workflow, UID, validation and DI wiring, i.e. become a new catch-all, and it would hide which
domain port each adapter fulfils.

## Decision

### One rule for every adapter

```text
Infrastructure/<Concern>/<Technology>/
```

* **First level = a concern**, which usually matches a domain port
  (`Storage` ↔ `StorageGatewayInterface`, `Encryption` ↔ `CipherGatewayInterface`,
  `Identity` ↔ `GeneratorInterface` / `IdValidatorInterface`, `Event` ↔ `DomainEventPublisher`,
  `Workflow` ↔ `WorkflowEngineInterface`).
* **Second level = the technology** that implements it. A replacement technology is added as a
  sibling folder (`Storage/Local`, `Encryption/OpenSsl`...) without touching the existing one.
* Infrastructure-only types tied to one technology (e.g. `StorageKey`, a Flysystem object key)
  live next to their adapter, never in the Domain.
* No pattern-named (`Gateway`, `Adapter`) or catch-all (`Service`, `Utils`, `Helper`) folder.

### Resulting layout

```text
Infrastructure/
├─ Encryption/Sodium/        # SodiumCipherGateway (was Gateway/)
├─ Event/Symfony/            # SymfonyDomainEventDispatcher + Listener/
├─ Identity/SymfonyUid/      # UUID generators and validator (was Service/)
├─ Persistence/Doctrine/     # unchanged
├─ Presentation/ApiPlatform/ # unchanged
├─ Storage/Flysystem/        # FlysystemStorageGateway (was MinioStorageGateway) + StorageKey
└─ Workflow/Symfony/         # SymfonyWorkflowEngine
```

* `MinioStorageGateway` is renamed `FlysystemStorageGateway`: the class depends on Flysystem's
  `FilesystemOperator`; MinIO is only the backend configured in `flysystem.yaml`.
* `Service/Comparator` was unused and is removed.

## Consequences

### Positive
* The folder tree reads as the list of ports and their implementations.
* ADR 0008's open point on `Gateway` is resolved.
* Swapping a technology is local to one `<Concern>/` folder.

### Negative
* Every moved class changes namespace (`config/services.yaml`, `use` statements, tests).
* One more folder level for concerns that have a single implementation today.
