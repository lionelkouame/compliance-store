# ADR 0003: Decoupling API Platform from Clean Architecture via State Processors / Providers

* **Status**: Accepted
* **Date**: 2026-07-24
* **Authors**: Architecture Team
* **Project**: Compliance Store

---

## Context

By default, API Platform encourages annotating Doctrine Entities directly (`#[ApiResource]` and `#[ORM\Column]`) in the same file.

Within **Compliance Store**, this standard approach has three major drawbacks:
1. **Violation of DDD & Clean Architecture**: The Domain layer would become coupled to the HTTP framework (API Platform) and the persistence system (Doctrine ORM).
2. **Bypassing security rules**: Direct database writes by API Platform would skip the compliance-check, PII-masking and encryption (`CipherPort`) pipeline.
3. **Rigid API contracts**: Impossible to evolve the HTTP response model independently of the internal data structure.

---

## Decision

We decide to strictly separate the HTTP Exposure layer (API Platform) from the business Domain:

1. **Pure Domain Entities (`src/Domain/Entity/`)**:
   * Pure PHP classes with no `#[ApiResource]` attribute and no Doctrine annotation.
   * Contain only business logic, invariants and behavior.

2. **API Exposure Resources (`src/Infrastructure/ApiPlatform/Resource/`)**:
   * Lightweight DTOs carrying the `#[ApiResource]` attributes to define REST endpoints and OpenAPI/Swagger documentation.

3. **Delegation to Use Cases via State Processors & Providers**:
   * **Reads (`GET`)**: Handled by a `StateProviderInterface` (`src/Infrastructure/ApiPlatform/State/`) that calls the `ConsultDocumentUseCase`.
   * **Writes (`POST`/`DELETE`)**: Handled by a `StateProcessorInterface` that forwards the input to the `StoreDocumentUseCase`.

---

## Exposure Flow Architecture

```text
HTTP Request (JSON/REST)
       │
       ▼
[DocumentResource DTO] (Infrastructure/ApiPlatform/Resource)
       │
       ▼
[DocumentStateProcessor / Provider] (Infrastructure/ApiPlatform/State)
       │
       ▼
[StoreDocumentUseCase / ConsultDocumentUseCase] (Application/UseCase)
       │
       ▼
[Domain Entity & Business Rules] (Domain)
       │
       ▼
[CipherPort & StorageGateway] (Infrastructure)
```

---

## Consequences

### Positive
* **100% Pure Domain**: No dependency on Symfony, API Platform or Doctrine in the domain.
* **Security guarantee**: Impossible to save a document without going through the Use Case (which runs PII redaction and Libsodium encryption).
* **API evolvability**: Easy to create API versions (v1, v2) with different DTOs without impacting business logic.

### Negative
* Requires creating DTOs and `StateProcessor`/`StateProvider` classes instead of using API Platform's automatic CRUD generator.
