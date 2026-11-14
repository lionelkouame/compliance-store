---
sidebar_position: 2
---

# Architecture

Compliance Store follows the principles of **Clean Architecture** (Robert C. Martin) and **Domain-Driven Design**. The goal: keep the business core independent of any framework or technical detail, so it stays testable and easy to evolve.

## The three layers

```text
src/
├── Domain/            # Pure business logic, zero external dependency
│   ├── Entity/          # Entities & aggregates (Holder, Document, StorageSpace...)
│   ├── ValueObject/     # Immutable value objects
│   ├── ReadModel/       # Read-only projections returned by use cases
│   ├── Exception/       # Business exceptions, grouped by category
│   └── Port/            # Interfaces (hexagonal ports)
│
├── Application/       # Use case orchestration
│   └── UseCase/         # One folder per action: Request, Response, UseCase
│
└── Infrastructure/    # Technical implementations
    ├── Gateway/         # Encryption (Sodium)
    ├── Storage/         # Storage (MinIO)
    ├── Persistence/     # Doctrine repositories (PostgreSQL)
    └── Presentation/    # API Platform: resources, DTOs, processors / providers
```

## Dependency rule

Dependencies always point **inward**:

- `Infrastructure` depends on `Application` and `Domain`.
- `Application` depends on `Domain`.
- `Domain` depends on nothing (plain PHP, no Symfony or Doctrine).

## The life of a request

Every API call follows the same path, from the outside to the business core and back:

```text
JSON ──► Input (DTO) ──► Request ──► Use case ──► Response (read model) ──► Output (DTO) ──► JSON
```

- Input and output **DTOs** belong to the presentation layer: they only hold simple types.
- The **use case** builds the value objects, applies the business rules, and never returns an entity — only a read-only projection (*read model*).
- API Platform **processors / providers** only convert from one format to another.

## Errors

The business core knows nothing about HTTP. It states a fact ("this holder already exists", "this value is invalid") by throwing an exception of a given **category**. The translation into an HTTP status happens **once**, by configuration:

| Category                | Meaning                              | HTTP status |
| ----------------------- | ------------------------------------ | ----------- |
| `NotFoundException`     | The requested item does not exist    | 404         |
| `AlreadyExistException` | The item already exists              | 409         |
| `InvalidDataException`  | The data or the operation is invalid | 422         |

Error responses use the standard `application/problem+json` format (RFC 7807), and their messages never contain personal data.

## Full documentation

Detailed architecture decisions are recorded as **ADRs (Architecture Decision Records)** in the code repository, under [`docs/architecture/adr/`](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr). That's the technical source of truth; the pages on this site give a simplified, up-to-date overview.
