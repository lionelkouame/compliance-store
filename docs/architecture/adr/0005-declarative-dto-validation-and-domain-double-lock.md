# ADR 0005: Declarative DTO Validation with Infrastructure Delegation and Domain Double-Lock Guard

* **Status**: Accepted
* **Date**: 2026-08-01
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store

---

## Context

Within **Compliance Store**, input data validation was fragmented across layers:
1. **The Pragmatic / DX Approach**: Declaring format validation rules directly on HTTP DTOs using Symfony Validator (`#[Assert\...`]`). This allows API Platform 4 to automatically return standard `HTTP 422 Unprocessable Content` (ConstraintViolation / Problem Details) responses. However, if business uniqueness rules exist only on HTTP DTOs, non-HTTP channels (CLI commands, async queue workers, unit tests) bypass validation.
2. **The Strict / DDD Approach**: Enforcing business validation exclusively inside Domain Use Cases via custom Domain Exceptions (`RegulatoryScopeAlreadyExistsException`). While pure, this leads to rule fragmentation (format on DTO vs business in UseCase) and requires custom HTTP exception listeners to standardize 422/409 responses.

**Problem Statement**: How to centralize the entire validation contract (format + business) in a single place for Developer Experience (DX) while guaranteeing uncompromised Domain security across all entry channels without violating Clean Architecture principles?

---

## Decision

We decide to adopt **Declarative Validation with Infrastructure Delegation** paired with a **Domain Double-Lock Guard (Defense in Depth)**:

### 1. Centralized Declarative Contract on DTOs (`src/Infrastructure/ApiPlatform/Resource/`)
* **All input constraints** (format `#[Assert\Regex]` AND business rules `#[AssertRegulatoryScopeCodeUnique]`) are declared at the top of the HTTP DTO resource.
* **The DTO belongs to the Infrastructure layer**: It has full legitimacy to carry Symfony Validator attributes.

### 2. Delegation via Dependency Inversion Principle (DIP)
* Custom business constraints (e.g. `AssertRegulatoryScopeCodeUnique`) utilize an Infrastructure validator (`AssertRegulatoryScopeCodeUniqueValidator`).
* The validator depends exclusively on a **Domain Port Interface** (`RegulatoryScopeRepositoryInterface`) to execute the check (`existsByCode()`).
* **The Domain remains 100% Pure**: No dependency on `Symfony\Component\Validator` enters the `src/Domain/` layer.

### 3. Domain Double-Lock Guard in Use Cases
* The Use Case (`CreateRegulatoryScopeUseCase`) **imperatively retains** its internal domain check (`if ($repo->existsByCode($code)) throw RegulatoryScopeAlreadyExistsException`).
* This guard guarantees database integrity for non-HTTP channels (CLI console commands, RabbitMQ/Kafka async workers, unit tests).

---

## Exposure & Validation Flow Architecture

```text
HTTP Request (JSON/REST)
       │
       ▼
[RegulatoryScopeResource DTO] (Infrastructure/ApiPlatform/Resource)
       │  ├── #[Assert\NotBlank] (Format)
       │  ├── #[Assert\Regex] (Format)
       │  └── #[AssertRegulatoryScopeCodeUnique] (Declarative Business Constraint)
       │
       ▼
[AssertRegulatoryScopeCodeUniqueValidator] (Infrastructure/ApiPlatform/Validator)
       │
       ├──► Injects [RegulatoryScopeRepositoryInterface] (Domain Port)
       └──► Calls existsByCode()
       │
       ├── (On Duplicate) ──► HTTP 422 Unprocessable Content (ConstraintViolationList)
       │
       ▼ (On Valid)
[CreateRegulatoryScopeUseCase] (Application/UseCase)
       │
       ├── 🛡️ Double-Lock Guard: existsByCode()
       │      └──► (On violation via CLI/Queue) ──► throws RegulatoryScopeAlreadyExistsException
       │
       ▼
[RegulatoryScope Entity & Persistence] (Domain & Infrastructure)
```

---

## Implementation Details (`compliance-store`)

1. **Custom Constraint Attribute**: `src/Infrastructure/ApiPlatform/Validator/Constraint/AssertRegulatoryScopeCodeUnique.php`
2. **Constraint Validator (DIP)**: `src/Infrastructure/ApiPlatform/Validator/ConstraintValidator/AssertRegulatoryScopeCodeUniqueValidator.php`
3. **DTO Resource**: `src/Infrastructure/ApiPlatform/Resource/V1/RegulatoryScopeResource.php`
4. **Use Case Guard**: `src/Application/UseCase/CreateRegulatoryScope/CreateRegulatoryScopeUseCase.php`
5. **Unit & Integration Tests**: `tests/Infrastructure/ApiPlatform/Validator/AssertRegulatoryScopeCodeUniqueValidatorTest.php` and `tests/Api/RegulatoryScopeApiTest.php`

---

## Consequences

### Positive
* **Single Source of Truth (DX)**: The complete input contract (format + business) is fully visible and self-documented on the DTO during Code Review.
* **Native HTTP 422 Responses**: API Platform 4 natively generates standard `HTTP 422 Unprocessable Content` responses pointing directly to the invalid property (`code`).
* **100% Pure Domain**: The Domain remains free of framework dependencies.
* **Multi-Channel Defense in Depth**: Non-HTTP callers (CLI, async workers) remain 100% protected by the Use Case double-lock guard.

### Negative
* Requires creating custom constraint attribute and validator classes in `src/Infrastructure/ApiPlatform/Validator/`.
