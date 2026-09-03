---
sidebar_position: 2
---

# Architecture

Compliance Store follows the principles of **Clean Architecture** (Robert C. Martin) and **Domain-Driven Design**. The goal: keep the business core independent of any framework or technical detail, so it stays testable and easy to evolve.

## The three layers

```text
src/
├── Domain/           # Pure business logic, zero external dependencies
│   ├── Entity/        # Entities & aggregates (Document, Policy, Rule)
│   ├── ValueObject/    # Immutable value objects
│   ├── Service/        # Stateless domain services
│   ├── Event/          # Domain events
│   ├── Exception/      # Domain exceptions
│   └── Port/            # Interfaces (hexagonal ports)
│
├── Application/       # Use case orchestration
│   ├── UseCase/         # StoreDocumentUseCase, ConsultDocumentUseCase...
│   └── Dto/              # Input/output objects
│
└── Infrastructure/    # Technical implementations
    ├── Gateway/          # Storage (MinIO), encryption (Sodium)...
    ├── Persistence/       # Doctrine repositories (PostgreSQL)
    └── ApiPlatform/        # API Platform state processors / providers
```

## Dependency rule

Dependencies always point **inward**:

- `Infrastructure` depends on `Application` and `Domain`.
- `Application` depends on `Domain`.
- `Domain` depends on nothing (plain PHP, no Symfony or Doctrine).

## Full documentation

Detailed architecture decisions are recorded as **ADRs (Architecture Decision Records)** in the code repository, under [`docs/architecture/adr/`](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr). That's the technical source of truth; the pages on this site give a simplified, up-to-date overview.
