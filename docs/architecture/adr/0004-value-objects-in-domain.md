# ADR 0004: Mandatory Value Objects in the Domain (no primitives, no native arrays)

* **Status**: Accepted
* **Date**: 2026-07-28
* **Authors**: Architecture Team
* **Project**: Compliance Store

---

## Context

The Domain layer (`src/Domain/`) is responsible for carrying business invariants (see [Architecture Overview](../overview/index.md)). Modeling an Entity's properties with primitive types (`string`, `bool`, `array`) leads to the classic *Primitive Obsession* risks:

1. **Scattered invariants**: nothing prevents an invalid `string $code` (wrong format) or an `array $allowedDocumentTypes` containing an empty string from flowing through the Domain — validation has to be repeated at every entry point.
2. **Loss of business meaning**: a `string` or `array` documents neither its expected format nor its validity rules; the type carries no contract.
3. **Implementation leakage**: exposing `array` as the return type of a Domain method (`allowedDocumentTypes(): array`) encourages callers to manipulate a generic data structure directly instead of a business concept ("allowed document types").

---

## Decision

1. **Every scalar property of a Domain Entity is carried by a dedicated Value Object**, immutable (`final readonly`), which validates its invariant in the constructor and throws an exception when the value is invalid.
   * Example: `RegulatoryScopeCode`, `RegulatoryScopeLabel`, `RegulatoryScopeDescription`.
2. **Lists are never exposed as `array` in the Domain's public surface.** A collection of Value Objects is itself modeled as a dedicated Value Object implementing `IteratorAggregate` and `Countable`, consumed via `foreach`, `count()` and `contains()` — never via a `toArray()` method exposed to the rest of the Domain.
   * Example: `AllowedDocumentTypes` (a collection of `DocumentType`).
3. **Conversion to/from a raw array remains an Infrastructure concern**, never a Domain responsibility:
   * Custom Doctrine Types (`src/Infrastructure/Persistence/Type/`) handle the VO ↔ SQL column conversion.
   * Mapping to `#[ApiResource]` DTOs (`src/Infrastructure/ApiPlatform/Resource/`) handles the VO ↔ JSON conversion, by iterating over the collection.
4. **Application-layer input DTOs (`Application/UseCase/*/*Command`) stay primitive.** These are boundary objects crossing HTTP (de)serialization; it is the Use Case's responsibility to build Value Objects from these primitives before calling into the Domain.
5. **Exception**: types that are already immutable and self-documenting (`bool`, `\DateTimeImmutable`) do not need a dedicated wrapper — the concern is primitive obsession over *untyped* primitives (`string`, `array`), not eliminating every native type.

---

## Concrete example: `RegulatoryScope`

```text
Application/UseCase/CreateRegulatoryScope/CreateRegulatoryScopeCommand (primitives, boundary)
       │  builds the VOs
       ▼
Domain/ValueObject/RegulatoryScopeCode, RegulatoryScopeLabel, RegulatoryScopeDescription
Domain/ValueObject/AllowedDocumentTypes (collection of DocumentType, IteratorAggregate + Countable)
       │
       ▼
Domain/Entity/RegulatoryScope (only exposes Value Objects, never a raw string/array)
       │
       ▼
Infrastructure/Persistence/Type/*Type.php   → VO ↔ SQL column conversion (JSON, VARCHAR...)
Infrastructure/ApiPlatform/Resource/*.php   → VO ↔ JSON conversion exposed by the API (iteration)
```

---

## Consequences

### Positive
* **Each business rule is validated in a single place** (code format, label length, non-empty document type...): the constructor of the relevant Value Object.
* **Typing documents the contract**: `RegulatoryScopeCode $code` says more than `string $code`.
* **Invalid state is unrepresentable**: a `RegulatoryScope` can only exist with already-validated VOs.

### Negative
* More classes to write for a single business concept (one VO per scalar property, one dedicated collection per list).
* Requires a custom Doctrine Type for every scalar VO mapped to the database (see `config/packages/doctrine.yaml` → `doctrine.dbal.types`), instead of native types (`string`, `json`...).
