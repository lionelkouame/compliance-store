# ADR 0010: Binary Responses Escape the State Provider Contract

* **Status**: Accepted
* **Date**: 2026-09-11
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store

---

## Context

**ADR 0003** requires every HTTP operation to be served through API Platform's state
layer: reads by a `StateProviderInterface`, writes by a `StateProcessorInterface`, each
delegating to an Application Use Case. Its purpose is stated in that ADR: prevent API
Platform from touching the database on its own and thereby bypassing the pipeline that
performs compliance checks, PII redaction and envelope encryption (ADR 0002).

That rule carries an implicit assumption about the *shape* of a response. A provider's
signature is:

```php
public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
```

It returns an **object**, which API Platform then hands to the Serializer. Every response
produced this way is therefore a *serialized representation* — JSON-LD, JSON, whatever the
negotiated format is.

The `document-read` work introduces an operation that does not fit that shape:

```text
GET /v1/documents/{id}/content   →   200 Content-Type: application/octet-stream
                                     the decrypted plaintext, byte for byte
```

The response body is not a representation of a resource; it *is* the resource's content.
Three distinct problems follow:

1. **No return path.** A provider cannot return bytes. Returning a wrapper object only
   moves the problem into the Serializer, which has no business normalizing a binary blob.
2. **The obvious workaround degrades the contract.** Base64 inside a JSON envelope keeps
   the letter of ADR 0003, at the cost of ~33% payload inflation, full buffering of the
   document on both ends, and an API contract that must be broken the day streaming reads
   arrive.
3. **The intent of ADR 0003 is not actually at stake here.** That ADR exists to stop API
   Platform from *writing* behind the Use Case's back. A read operation that delegates
   every decision to `ConsultDocumentUseCase` — identifier validation, storage read,
   decryption, integrity verification — bypasses nothing. Only the transport differs.

ADR 0003 is right about the pipeline and silent about non-serializable responses. Rather
than diverge from it quietly, this ADR writes the exception down and bounds it.

---

## Decision

An HTTP operation whose response body is **not a serializable representation** MAY be
served by a dedicated Symfony controller wired to the API Platform operation, instead of a
State Provider — under all of the conditions below. Every other operation remains bound by
ADR 0003 without exception.

### 1. Scope — non-serializable responses only

The escape applies exclusively to operations returning raw bytes, a stream, or a file
download. Any operation whose body is a structured representation (JSON, JSON-LD, HAL,
CSV export of a collection…) stays on a provider or a processor. Performance, convenience,
or an awkward serialization group are **not** grounds for a controller.

### 2. Reads only

The escape covers `GET` operations. Writes stay on `StateProcessorInterface` with no
exception whatsoever: preventing an out-of-pipeline write is the entire reason ADR 0003
exists, and nothing in this ADR relaxes it.

### 3. The controller delegates, it does not decide

The controller is a transport adapter. It may only:

* read its route parameters,
* call a single Application Use Case,
* translate the result — or the exceptions — into an HTTP response.

It MUST NOT contain business rules, and MUST NOT reach for a repository, an
`EntityManager`, a `StorageGatewayInterface`, a `CipherGatewayInterface`, or any other
port. If a controller needs one of those, the logic belongs in the Use Case.

### 4. Location and declaration

Controllers live under `src/Infrastructure/Presentation/ApiPlatform/V1/Controller/`,
following the version-first layout of **ADR 0008**. The operation declares the controller
explicitly and disables the state/serialization pipeline it no longer uses:

```php
new Get(
    uriTemplate: '/documents/{id}/content',
    controller: GetDocumentContentController::class,
    read: false,
    output: false,
    openapi: new Operation(
        tags: ['V1 - Documents'],
        summary: 'Download the decrypted content of a document',
        responses: [/* declared by hand — see Consequences */],
    ),
)
```

### 5. Typed route parameters

**ADR 0007** requires State Providers to declare their URI variables as a PHPDoc array
shape, so that no `mixed` reaches a strictly-typed Use Case. A controller obtains the same
guarantee from the language itself and MUST use it: route parameters are declared as typed
arguments of `__invoke()` (`public function __invoke(string $id): Response`). Symfony
Routing performs the binding; PHPStan at level max enforces the type. Reading from an
untyped `array $uriVariables` inside a controller is not permitted.

### 6. Error mapping stays in Presentation

The Domain knows nothing about HTTP. The controller maps domain and infrastructure
exceptions to status codes, exactly as the providers already do, and MUST NOT propagate an
infrastructure message to the client when that message can disclose internal
topology — storage keys, bucket names, host names. Such a response carries a generic
message, with the original exception preserved as `previous` for the logs.

### 7. OpenAPI documentation remains mandatory

Because `output: false` removes the auto-generated response schema, the operation MUST
declare its documentation by hand — at minimum the success status, the produced content
type, and each error status it can return. An undocumented binary endpoint is not
acceptable.

---

## Relationship to ADR 0003

ADR 0003 remains **Accepted** and unmodified. This ADR does not supersede it: it records a
bounded exception to the response-shape assumption embedded in it, for operations that
assumption cannot cover. The pipeline guarantee of ADR 0003 — *nothing reaches the Domain
or the storage backend except through an Application Use Case* — is preserved in full by
conditions 2 and 3 above.

```text
HTTP GET /v1/documents/{id}/content
       │
       ▼
[GetDocumentContentController]  (Presentation/ApiPlatform/V1/Controller)  ← the exception
       │                          reads the route parameter, maps errors
       ▼
[ConsultDocumentUseCase]        (Application/UseCase)                     ← unchanged
       │                          validates, reads, decrypts, verifies integrity
       ▼
[Domain Entity & Business Rules]
       │
       ▼
[CipherGateway & StorageGateway]
```

The only segment that differs from ADR 0003's flow is the first one. Everything below it
is identical.

---

## What this ADR does not authorize

* A controller for a JSON endpoint, for any reason.
* A controller on a write operation.
* Direct Doctrine, storage or cipher access from the Presentation layer.
* Reintroducing `#[ApiResource]` on a Domain entity, or any other relaxation of ADR 0003.

---

## Consequences

### Positive

* **The transport matches the contract.** A document download is a transfer of bytes, and
  the API says so — no base64 envelope, no 33% inflation, no double buffering.
* **Streaming stays reachable.** Returning a `StreamedResponse` later is a change inside
  the controller and the Use Case's return type, not a breaking change to the public API
  contract. Had the content been published as a base64 JSON field, it would have been one.
* **The pipeline guarantee is untouched.** Encryption, integrity verification and every
  other Use Case responsibility still run on the only path that exists.
* **No tooling change required.** `deptrac.yaml` already allows
  `Infrastructure → Application` and `Infrastructure → Symfony`; a delegating controller
  introduces no new dependency direction.

### Negative

* **Two exposure idioms coexist** in `Presentation/ApiPlatform/V1/`. A reader must now
  know which one applies to a given operation; conditions 1 and 2 are what makes that
  determinable without reading the implementation.
* **Hand-written OpenAPI can drift.** With `output: false`, nothing checks the declared
  response schema against what the controller actually returns. An API test asserting the
  status code and `Content-Type` of the operation is the only real guard, and is therefore
  required alongside any such endpoint.
* **The boundary is not machine-enforced.** Deptrac cannot distinguish a controller that
  delegates from one that does too much: both depend only on `Application` and `Symfony`.
  Condition 3 is upheld by review, not by a tool — the same way ADR 0009's classification
  is.
