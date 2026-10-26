# ADR 0013: API Clients Authenticate with Hashed Bearer API Keys

* **Status**: Accepted
* **Date**: 2026-10-24
* **Authors**: Architecture Team
* **Project**: Compliance Store

---

## Context

Until this decision every endpoint of the API was reachable anonymously: anyone able to
join the network could register a holder, upload an identity document and read it back.
For a service whose purpose is to store sensitive personal documents, access control is a
precondition to any deployment, not a feature.

The callers of this API are **machines**: back-offices, partner systems, batch jobs. There
is no end user logging in, no browser session, no password to reset. The existing
architecture also sets constraints: the domain must stay ignorant of who is calling
(ADR 0001, ADR 0003), and every exposure decision must stay inside
`Infrastructure/Presentation` and the Symfony security layer.

Three options were weighed:

| Option | Pros | Cons |
| :--- | :--- | :--- |
| JWT issued by this API (`lexik/jwt-authentication-bundle`) | Standard, stateless | Needs a user store, a login endpoint, key pairs and token refresh for callers that are not people |
| Delegated OAuth2 / OpenID Connect | Fits a corporate identity provider | Requires an identity provider that does not exist yet; heavy for a first deployment |
| **Bearer API keys stored hashed** | Simple to issue and revoke, stateless, nothing to run alongside | No automatic expiry; rotation is a manual operation |

---

## Decision

Callers are **API clients** identified by a **bearer API key** (RFC 6750):

```http
Authorization: Bearer cs_7Kx...
```

### 1. The client is an infrastructure concern

`App\Infrastructure\Security\ApiClient` implements Symfony's `UserInterface`. It carries a
unique name, the **SHA-256 hash** of its key, its roles, a creation date and an optional
revocation date. It is persisted in its own table (`api_client`) with a dedicated Doctrine
mapping directory, outside `Domain/Entity`: the domain never sees it.

### 2. The clear key exists once

`ApiClient::issue()` draws 32 random bytes, encodes them URL-safe and prefixes them with
`cs_` so a leaked key is recognisable by log scanners and secret detectors. Only the hash is
stored. The clear key is printed once by `app:api-client:create` and is never recoverable;
`app:api-client:revoke` invalidates it immediately.

### 3. One stateless firewall, one authenticator

The `api` firewall (`^/api`) is stateless and uses Symfony's `access_token` authenticator
with `ApiKeyTokenHandler`, which looks the client up by hash and compares in constant time.
A missing, unknown or revoked key yields `401` with
`WWW-Authenticate: Bearer realm="Compliance Store API"`; the same message is used for
unknown and revoked keys so that nothing is learned from the difference.

### 4. Three hierarchical roles, declared on each operation

```text
ROLE_ADMIN  >  ROLE_OPERATOR  >  ROLE_READER
```

* `ROLE_READER`: reads (holders, documents metadata, storage spaces).
* `ROLE_OPERATOR`: registers holders and identity documents, stores documents, downloads
  decrypted content.
* `ROLE_ADMIN`: manages storage spaces, drives the GDPR erasure workflow, reads the audit trail.

Every API Platform operation declares its minimum role with the `security` attribute, so the
requirement is visible next to the operation it protects. `access_control` in
`security.yaml` adds a path-based safety net: anything under `/api` requires `ROLE_READER`
except the health check, the OpenAPI documentation and the JSON-LD contexts.

### 5. The authenticated client is the audit actor

The audit trail (`AuditTrailInterface`) records the client name as the actor of every
personal-data action. Outside a request the actor is `system`.

---

## Consequences

### Positive

* **Nothing anonymous reaches a use case.** The decision is enforced by the framework
  before any provider or processor runs, and covered by `AuthenticationApiTest`.
* **No new runtime component**: no identity provider, no token signing keys, no session store.
* **Revocation is immediate** and does not depend on token lifetimes.
* **The domain is untouched**: the only new domain concept is the audit trail, which
  receives an actor name as a string.

### Negative

* **No expiry, no automatic rotation.** A compromised key lives until someone revokes it.
  An `expires_at` column and a rotation command are the natural next step.
* **Bootstrapping is an operations task.** The first administrator key is created from the
  console inside the container; there is no self-service.
* **One key per integration.** Finer-grained authorisation (per holder, per storage space)
  would need a different model, probably scoped tokens.
* **API tests must authenticate.** Every API test creates a throwaway client through the
  `ApiClientFixture` trait, which adds a database round-trip per test.

---

## Relationship to other ADRs

* **ADR 0003**: access control is declared on operations and enforced by the framework;
  providers and processors keep delegating to one use case.
* **ADR 0011**: for binary operations `object` is a `Response`, so their `security`
  expression only uses roles, as this ADR prescribes.
