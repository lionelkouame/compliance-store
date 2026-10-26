---
sidebar_position: 3
---

# Architecture

Compliance Store follows the principles of **Clean Architecture** (Robert C. Martin) and **Domain-Driven Design**. The goal: keep the business core independent of any framework or technical detail, so it stays testable and easy to evolve.

## The stack

PHP 8.5 on FrankenPHP and Caddy, Symfony 8.1, API Platform 4.3, Doctrine ORM with PostgreSQL 16, Flysystem towards MinIO or any S3-compatible storage, Libsodium for encryption. Everything runs with Docker Compose.

## The three layers

```text
src/
├── Domain/            # Pure business logic: no Symfony, Doctrine or API Platform import
│   ├── Entity/          # Holder, Document, IdentityDocument, StorageSpace, AuditEvent
│   ├── ValueObject/     # HolderId, BirthDate, FileHash, WrappedDataKey, AuditAction…
│   ├── Exception/       # HolderNotFoundException, DocumentIntegrityException…
│   └── Port/            # Interfaces the domain expects
│       ├── Gateway/       # Encryption, storage
│       ├── Repository/    # Entity persistence
│       └── Service/       # Audit trail, identifier generation
│
├── Application/
│   └── UseCase/         # 15 use cases: StoreDocument, ConsultDocument, AnonymizeHolder…
│
└── Infrastructure/    # Technical implementations of the ports
    ├── Persistence/     # Doctrine repositories, XML mappings, DBAL types (PostgreSQL)
    ├── Presentation/    # API Platform resources, state providers and processors
    ├── Storage/         # S3 storage through Flysystem
    ├── Gateway/         # Libsodium envelope encryption
    ├── Security/        # API clients and API key authentication
    ├── Audit/           # Audit trail (actor = authenticated client)
    ├── Console/         # Key creation and revocation commands
    └── Service/         # UUID generation, validation
```

## Dependency rule

Dependencies always point **inward**:

- `Infrastructure` depends on `Application` and `Domain`.
- `Application` depends on `Domain`.
- `Domain` depends on nothing (plain PHP, no Symfony or Doctrine).

Deptrac enforces this rule on every commit, alongside PHPStan at its maximum level and the PHPUnit suite.

## How a request travels through the code

1. Caddy receives the HTTPS request and rejects bodies above 16 MiB right away.
2. Symfony Security checks the API key and the role.
3. API Platform routes to a resource; its provider (read) or processor (write) turns the request into a command.
4. The use case applies the business rules through entities and value objects.
5. The ports are served by Doctrine (PostgreSQL), the Libsodium gateway and the Flysystem gateway (S3).

## Architecture decisions

Thirteen ADRs record the structuring choices, available in [`docs/architecture/adr/`](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr):

| ADR | Decision |
|-----|----------|
| 0001 | Native compliance core and provider extension port |
| 0002 | Envelope encryption and Zero Trust storage |
| 0003 | Decoupling API Platform via state processors and providers |
| 0004 | Mandatory value objects in the domain |
| 0005 | Declarative DTO validation and domain double lock |
| 0006 | Non-nullable value objects, optionality handled by the domain |
| 0007 | Declarative URI variables contract in state providers |
| 0008 | Infrastructure refactoring |
| 0009 | Compliance relevance of Document's value objects |
| 0010 and 0011 | Binary responses served by state providers returning a Response |
| 0012 | Regulation engine extracted as an external provider |
| 0013 | API clients authenticate with hashed Bearer API keys |

They are the technical source of truth; this page gives a simplified view. The [full technical documentation](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/10-documentation-technique.md) (in French) details the data model, every use case, encryption, authentication and the API reference.
