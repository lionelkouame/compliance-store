# ADR 0013: Business Exceptions Categorised in the Domain, Translated to HTTP Once at the Boundary

* **Status**: Accepted
* **Date**: 2026-11-14
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store

---

## Context

During the transition to the use case architecture introduced on the Holder feature
(Request / Response / read model, see the [architecture overview](../overview/index.md)),
two ways of handling errors coexisted, and neither was satisfactory.

**Legacy paradigm (StorageSpace, IdentityDocument).** Every processor and provider caught
the use case exceptions itself and rethrew a Symfony `HttpException`:

```text
 POST /api/v1/storage-spaces
          │
          ▼
 ┌──────────────────────────────────┐
 │ CreateStorageSpaceProcessor      │
 │  try {                           │
 │    useCase->execute(...)  ───────┼──► CreateStorageSpaceUseCase
 │  } catch (CodeAlreadyExists) {   │          │ throw StorageSpaceCode
 │    throw ConflictHttpException ◄─┼──────────┘ AlreadyExistsException
 │  } catch (InvalidArgument) {     │
 │    throw UnprocessableEntity...  │
 │  }                               │
 └──────────────────────────────────┘
          │
          ▼
   409 / 422  ✔  (but the mapping is copied into every processor)
```

The status codes were right, but:

* the same mapping was **duplicated** in every processor and provider;
* a processor that "forgot" a `catch` returned a **500**;
* every adapter had to **know** every exception its use case could throw.

**New paradigm (Holder), before this ADR.** `CreateHolderProcessor` delegated cleanly to
its use case and caught nothing — so nothing translated the business exceptions:

```text
 POST /api/v1/holders  { email already used }
          │
          ▼
 ┌──────────────────────────┐      ┌──────────────────────────────────┐
 │ CreateHolderProcessor    │ ───► │ CreateHolderUseCase              │
 │  (no try/catch)          │      │  throw HolderAlreadyExists       │
 └──────────────────────────┘      │        Exception                 │
          ▲                        └──────────────────────────────────┘
          │  unmapped exception
          ▼
   500 Internal Server Error  ✘  (409 Conflict expected)
```

Value Objects made it worse: they threw a generic `\InvalidArgumentException`, which also
surfaced as a 500 instead of a 422 — and which can equally come from a bug or a library, so
it cannot tell a client error from a programming error.

**Problem statement**: how can the application handle business errors *transparently* —
without every adapter knowing every exception — while keeping the Domain free of any HTTP
notion (`deptrac.yaml`: `Domain: []`, `Application: [Domain]`)?

---

## Decision

### 1. An exception states a business fact, never an HTTP status

`HolderAlreadyExistsException` says "this holder already exists". Whether that becomes a 409
in REST, a GraphQL error or a CLI exit code is the concern of the layer that exposes it.
No `HttpException` may appear in `src/Domain` or `src/Application`.

### 2. Exceptions are classified by category, in the Domain

Every business exception extends one **abstract category** in `src/Domain/Exception/`, which
itself extends `App\Domain\Exception\DomainException` (a `\DomainException`):

```text
 \DomainException
   └── App\Domain\Exception\DomainException
         ├── NotFoundException          (abstract)  the requested aggregate does not exist
         │     ├── HolderNotFoundException
         │     ├── NaturalPersonNotFoundException
         │     └── StorageSpaceNotFoundException
         ├── AlreadyExistException      (abstract)  a uniqueness invariant would be violated
         │     ├── HolderAlreadyExistsException
         │     ├── IdentityDocumentAlreadyExistsException
         │     └── StorageSpaceCodeAlreadyExistsException
         ├── InvalidDataException       (abstract)  the data or the requested transition is invalid
         │     ├── InvalidValueException             ◄── thrown directly by Value Objects
         │     ├── IdentityDocumentExpiredException
         │     ├── InvalidHolderStatusTransitionException
         │     ├── InvalidStorageSpaceStatusTransitionException
         │     └── UnknownDocumentHolderException
         └── PolicyViolationException   (abstract)  reserved — see "Open point" below
```

A new business exception only has to extend the right category: **no HTTP code changes**.

### 3. Value Objects throw `InvalidValueException`

`InvalidValueException` is the only **concrete** category member: Value Objects throw it
directly instead of declaring one exception class each.

```php
namespace App\Domain\ValueObject\Document;

use App\Domain\Exception\InvalidValueException;

final readonly class Email
{
    public function __construct(public string $value)
    {
        if ('' === trim($this->value)) {
            throw new InvalidValueException('The email cannot be empty.');
        }
        // ...
    }
}
```

`\InvalidArgumentException` is **forbidden** in `src/Domain` and `src/Application`. It remains
legitimate in Infrastructure for technical failures (`SodiumCipherGateway` master key,
`StorageKey`), which must stay a 500.

### 4. Use cases throw; adapters never catch

The use case raises the business exception and does not know it is called over HTTP.
Processors and providers are thin adapters (DTO → Request → use case → Response → DTO)
**without any `try/catch`**:

```php
public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StorageSpaceOutput
{
    Assert::isInstanceOf($data, CreateStorageSpaceInput::class);
    $this->validator->validate($data);

    $useCaseResponse = $this->useCase->execute(new CreateStorageSpaceRequest(
        code: $data->code,
        name: $data->name,
    ));

    return StorageSpaceOutput::fromReadModel($useCaseResponse->readModel);
}
```

A "not found" is an exception too: a use case never returns `null` (which would break the
`UseCaseInterface<Request, Response>` contract). A malformed identifier that cannot match
anything is reported as **not found** by the use case, not as invalid data.

### 5. Translation to HTTP happens once, by configuration

The mapping lives in a single place, `config/packages/api_platform.yaml`:

```yaml
api_platform:
    exception_to_status:
        # API Platform defaults: setting the key replaces them, so they are restated.
        Symfony\Component\Serializer\Exception\ExceptionInterface: 400
        ApiPlatform\Metadata\Exception\InvalidArgumentException: 400
        Doctrine\ORM\OptimisticLockException: 409
        # Business exception categories (src/Domain/Exception).
        App\Domain\Exception\NotFoundException: 404
        App\Domain\Exception\AlreadyExistException: 409
        App\Domain\Exception\InvalidDataException: 422
```

This works with abstract classes because `ApiPlatform\Symfony\EventListener\ErrorListener`
matches with `is_a($exception::class, $class, true)`: a category covers all its subclasses.
The first matching entry wins, so if categories ever nest, the most specific is declared
first. Per-operation `exceptionToStatus` is merged **after** the global map.

API Platform then returns a standard **`application/problem+json`** (RFC 7807) response:

```json
{
  "type": "/errors/409",
  "title": "An error occurred",
  "status": 409,
  "detail": "A holder with this email already exists."
}
```

### 6. Messages never contain personal data

A 4xx `detail` is sent to the client **and** may be logged. Exception messages must not echo
personal data (identity document number, birth date, email...). Echoing non-personal
technical values (a country code, a storage space code, an id) is acceptable.

---

## Error Lifecycle

```text
  Client                 Processor            Use case             Domain (VO / Entity)
    │  POST /holders         │                    │                        │
    │───────────────────────►│                    │                        │
    │                        │ validate(DTO)      │                        │
    │◄── 422 if DTO invalid ─│ (declarative, ADR 0005)                     │
    │                        │ execute(Request)   │                        │
    │                        │───────────────────►│ BirthDate::make()      │
    │                        │                    │───────────────────────►│
    │                        │                    │  InvalidValueException │
    │                        │                    │◄───────────────────────│
    │                        │   (no catch)       │                        │
    │                        │◄───────────────────│                        │
    │                ErrorListener (API Platform) │                        │
    │                 exception_to_status → 422   │                        │
    │◄── 422 application/problem+json ────────────│                        │
```

The two levels of 422 complement each other — the **double lock** of ADR 0005:

* the **DTO** rejects shape errors early (missing field, wrong format);
* the **Domain** guarantees its invariants whatever the entry channel (CLI, message bus, tests).

### Status code reference

| Situation                                             | Raised by                                     | Status |
| ----------------------------------------------------- | --------------------------------------------- | ------ |
| Body is not parseable (malformed JSON)                | API Platform deserializer                     | 400    |
| Field of the wrong type (e.g. number for a string)    | Deserializer (`collectDenormalizationErrors`) | 422    |
| Missing / blank field                                 | DTO constraints (ADR 0005)                    | 422    |
| Value breaks a Value Object invariant                 | `InvalidValueException`                       | 422    |
| Forbidden transition, expired document                | `InvalidDataException` subclass               | 422    |
| Uniqueness violated                                   | `AlreadyExistException` subclass              | 409    |
| Aggregate not found, or id that cannot match anything | `NotFoundException` subclass                  | 404    |
| Anything else (bug, infrastructure failure)           | —                                             | 500    |

---

## Implementation Details (`compliance-store`)

1. **Categories**: `src/Domain/Exception/{DomainException,NotFoundException,AlreadyExistException,InvalidDataException,PolicyViolationException,InvalidValueException}.php`
2. **Mapping**: `config/packages/api_platform.yaml` (`exception_to_status`)
3. **Adapters without `try/catch`**: `src/Infrastructure/Presentation/ApiPlatform/V1/State/{Processor,Provider}/<Feature>/`
4. **Guard**: `tests/Unit/Architecture/NoInvalidArgumentExceptionInCoreTest.php` fails if
   `InvalidArgumentException` appears in `src/Domain` or `src/Application`.
5. **HTTP contract tests**: `tests/Api/*ApiTest.php` assert 404 / 409 / 422 for every feature.

### Migration path followed (strangler pattern)

The decision was rolled out progressively, each step keeping the API tests green:

```text
 Step 1  Foundation        categories + exception_to_status → no existing behaviour changes
 Step 2  Adapters          try/catch removed from every processor/provider; a temporary
                           per-operation `exceptionToStatus: [\InvalidArgumentException => 422]`
                           kept Value Object errors at 422
 Step 3  Value Objects     \InvalidArgumentException → InvalidValueException in every VO;
                           the temporary per-operation mappings were removed
 Step 4  Lock              architecture test forbidding \InvalidArgumentException in the core
```

### Differences from the initial study

* Category names follow the existing code: `AlreadyExistException` (not `AlreadyExists`),
  and a single `InvalidDataException` category covers both broken value invariants and
  forbidden transitions (the study proposed separate `InvalidValue` and
  `BusinessRuleViolation` categories). Both map to 422, so splitting them brought no value.
* The guard is a PHPUnit architecture test rather than a PHPStan rule: it needs no extra
  extension.

---

## Alternatives Considered

| Approach                                  | When to use it                                                  | Verdict                                                                                                         |
| ----------------------------------------- | --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Global `exception_to_status` (**chosen**) | General case: one HTTP status per business category             | Chosen: zero code, native `problem+json` and OpenAPI support                                                    |
| Per-operation `exceptionToStatus`         | One operation that must return something else for one exception | Allowed as a local override; was used as a transition bridge                                                    |
| Dedicated `kernel.exception` listener     | Enriched error body (stable business error code, extra fields)  | Deferred: possible later via an `errorCode()` on `DomainException`                                              |
| Use case returning a `Result` / `Either`  | Very frequent "expected" errors, without the cost of exceptions | Rejected: must be propagated by hand in every use case and adapter — the opposite of transparent in PHP/Symfony |
| `try/catch` in each adapter (legacy)      | —                                                               | Rejected: duplicated, easy to forget, couples adapters to use case internals                                    |

---

## Relationship to other ADRs

* **ADR 0003** (state processors/providers): adapters stay thin; this ADR removes their last
  responsibility beyond mapping, error translation.
* **ADR 0005** (declarative DTO validation + domain double lock): the DTO 422 and the Domain
  422 are the two locks; this ADR defines how the Domain lock reaches the client.
* **ADR 0007** (URI variables contract): providers read `$uriVariables` declaratively and let
  the use case decide whether an id is "not found".
* **ADR 0011** (binary responses): binary providers go through the same error pipeline.

---

## Open point

`PolicyViolationException` exists but is **not mapped**: if thrown today it would surface as a
**500**. Its status (403 Forbidden vs 422) must be decided, and added to `exception_to_status`,
in the same change that introduces its first concrete subclass.

---

## Consequences

### Positive
* **Transparent**: adding a business exception requires no HTTP change — it inherits its
  category's status.
* **Single source of truth**: the exception → status mapping lives in one configuration file.
* **Pure core**: Domain and Application stay free of HTTP; the rule is enforced by Deptrac and
  by the architecture test.
* **Standard errors**: every error is an RFC 7807 `problem+json` response, documented in OpenAPI.
* **Bugs stay visible**: `\Throwable`, `\Exception` and `\InvalidArgumentException` are never
  mapped, so a programming error remains a logged 500 instead of a misleading 4xx.

### Negative
* Every new business exception **must** extend a category; one extending
  `DomainException` directly (or `\Exception`) silently becomes a 500.
* Error responses carry a generic `title` and the exception message as `detail`; clients
  needing a stable machine-readable error code will require the listener alternative.
* Exception messages are part of the public API contract (and must stay free of personal data).
