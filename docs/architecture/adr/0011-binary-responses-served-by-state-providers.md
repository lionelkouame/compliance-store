# ADR 0011: Binary Responses Are Served by State Providers Returning a Response

* **Status**: Proposed
* **Date**: 2026-09-16
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store
* **Supersedes**: [ADR 0010](0010-binary-responses-escape-state-provider-contract.md)

---

## Context

**ADR 0010** allowed a dedicated Symfony controller to serve operations whose response
body is not a serializable representation — a raw file download such as
`GET /v1/documents/{id}/content`. Its first and decisive argument was:

> *No return path. A provider cannot return bytes.*

That premise is **wrong**. With the default `MainController` (`use_symfony_listeners:
false`), every API Platform operation runs through the chain
**Provider → `SerializeProcessor` → `RespondProcessor`**, and both processors let a
Symfony `Response` through untouched (API Platform core 4.3):

```php
// ApiPlatform\State\Processor\SerializeProcessor::process()
if ($data instanceof Response || !$operation->canSerialize() || ...) {
    return $this->processor ? $this->processor->process($data, ...) : $data;
}

// ApiPlatform\State\Processor\RespondProcessor::process()
if ($data instanceof Response || !$operation instanceof HttpOperation) {
    return $data;
}
```

A provider returning a `Response` is therefore a path the framework supports on purpose,
not a workaround. API Platform's own guidance is to prefer providers and processors over
custom controllers.

The premise was verified end to end with a throwaway API test before this ADR was
written (provider returning a `Response`, operation declaring an `application/octet-stream`
output format):

| Request | Result |
| :--- | :--- |
| `Accept: application/octet-stream` or `*/*` | `200`, `Content-Type: application/octet-stream`, non-UTF-8 body byte-identical |
| `Accept: application/json` or `application/ld+json` | `406`, `application/problem+json` |
| provider throws `NotFoundHttpException` | `404`, `application/problem+json` |
| provider throws `HttpException(502)` with an infrastructure `previous` | `502`, generic detail, `previous` message absent from the body |
| operation declared with `output: false` | runtime identical, **but OpenAPI documents a `204`** |

PHPStan (level max) accepts `@implements ProviderInterface<Response>`; Deptrac reports no
violation.

ADR 0010's cost — two exposure idioms in `Presentation/ApiPlatform/V1/`, a rule
(*"the controller delegates, it does not decide"*) enforced by review only, and a
parallel convention for route parameters — bought nothing that a provider cannot do.

---

## Decision

An operation whose response body is **not a serializable representation** is served by a
**State Provider that returns a Symfony `Response`**. No controller. ADR 0003 applies to
it without exception, exactly as to every other operation.

### 1. Scope — non-serializable reads

A provider returns a `Response` only for raw bytes, a stream (`StreamedResponse`,
`BinaryFileResponse`) or a file download. Any structured representation (JSON, JSON-LD,
CSV export of a collection…) returns an object and goes through the Serializer as usual.
Writes stay on `StateProcessorInterface`.

### 2. Same provider rules as every other provider

* It delegates to a **single Application Use Case** and holds no business rule.
* It never reaches for a repository, an `EntityManager`, a storage or cipher gateway.
* It declares its URI variables as a PHPDoc array shape (**ADR 0007**) and its generic
  type: `@implements ProviderInterface<Response>`.
* It lives in `src/Infrastructure/Presentation/ApiPlatform/V1/State/` (**ADR 0008**).

### 3. The output format is declared on the operation

The operation MUST declare the media type it produces through `outputFormats`. Without it,
content negotiation falls back to the global formats (`jsonld`, `json`) and the operation
cannot legitimately answer `application/octet-stream`.

```php
new Get(
    uriTemplate: '/documents/{id}/content',
    outputFormats: ['binary' => ['application/octet-stream']],
    provider: GetDocumentContentProvider::class,
    openapi: new Operation(
        tags: ['V1 - Documents'],
        summary: 'Download the decrypted content of a document',
        responses: [/* declared by hand — see §5 */],
    ),
)
```

### 4. Do not set `output: false`

`output: false` does not change the runtime behaviour, but makes the OpenAPI factory
document the operation as a `204 No Content`. The operation keeps its default output and
its responses are documented by hand (§5).

### 5. OpenAPI responses are declared by hand

The generated schema describes the resource class, not the bytes actually returned. The
operation MUST declare in `openapi:` at least the success status with its content
(`application/octet-stream`, `type: string, format: binary`) and every error status it
can return.

### 6. Errors go through API Platform's error pipeline

The provider translates domain and infrastructure exceptions into `HttpException`s, as
existing providers do. API Platform renders them with its error formats
(`application/problem+json`), independently of the operation's output format. When the
underlying message can disclose internal topology — storage keys, bucket names, host
names — the `HttpException` carries a generic message and keeps the original exception
as `previous` for the logs.

---

## Relationship to other ADRs

* **ADR 0010** is superseded. Its intent — no base64 envelope, a transport that matches the
  contract, streaming kept reachable — is preserved; its mechanism is not.
* **ADR 0003** is untouched and now holds with no recorded exception.
* **ADR 0007** applies to binary providers as written.

```text
HTTP GET /v1/documents/{id}/content
       │
       ▼
[GetDocumentContentProvider]   (Presentation/ApiPlatform/V1/State)  ← an ordinary provider
       │                         returns a Response instead of a resource object
       ▼
[ConsultDocumentUseCase]       (Application/UseCase)
       │
       ▼
[Domain Entity & Business Rules]
       │
       ▼
[CipherGateway & StorageGateway]
```

---

## Consequences

### Positive

* **One exposure idiom.** Every operation in `Presentation/ApiPlatform/V1/` is a provider or
  a processor; nothing to decide per operation.
* **No parallel conventions.** ADR 0007, the provider error-mapping idiom and the existing
  provider tests apply unchanged.
* **Streaming stays reachable.** Returning a `StreamedResponse` later is a change inside the
  provider and the Use Case's return type, not a change to the public contract.
* **The operation keeps API Platform's machinery**: content negotiation, error rendering,
  and the `security` attribute when access control arrives.

### Negative

* **`406` precedes `404`.** Content negotiation runs before the provider: a client sending
  `Accept: application/json` gets `406` even for an unknown identifier. This is correct HTTP,
  but it must be documented and covered by an API test.
* **API Platform's test client sends `Accept: application/ld+json` by default.** API tests on
  such operations must set `Accept` explicitly, or they will assert against a `406`.
* **Hand-written OpenAPI can drift.** An API test asserting the status code and the
  `Content-Type` of the operation is required alongside any such endpoint.
* **`security` expressions cannot rely on `object`.** For these operations `object` is the
  `Response`, not the domain resource. Access control on binary reads must be expressed
  without it — or enforced in the Use Case.
