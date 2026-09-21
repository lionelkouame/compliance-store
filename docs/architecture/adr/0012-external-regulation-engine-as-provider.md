# ADR 0012: Regulation Engine Extracted as an External, Reusable Provider

* **Status**: Proposed
* **Date**: 2026-09-21
* **Authors**: Architecture & Compliance Team
* **Project**: Compliance Store

---

## Context

**ADR 0001** classifies every compliance rule into one of two places:

| Origin of the change | Location of the rule |
| :--- | :--- |
| Legal / GDPR / regulatory change | **Domain (Native Core)** |
| Partner / technical vendor change | **Infrastructure (Provider)** |

RegEngine — the ternary rule evaluator described in `spec-v2.md` (locks, regulatory
frameworks, roles) — is squarely a *legal / regulatory* concern: it is what ADR 0001 calls
the native core, the thing that "guarantees hard legal invariants." Under ADR 0001 as
written, it cannot be a provider: the Provider Extension Port exists only for optional,
additive, technical/vendor logic that can never weaken the native core, and RegEngine is
the opposite of optional.

Lionel has now decided, independently of that classification, to build the regulation
engine as a **separate external application** (Go) rather than as in-process PHP inside
`compliance-store`'s domain. This ADR exists to write that decision down explicitly rather
than let `reg-engine-core` be designed against a native-core assumption ADR 0001 no longer
reflects — the lot 1a design is in progress under this exact assumption at the time of
writing.

**Rationale (2026-09-21):** the goal is not merely to move code out of `compliance-store`.
The regulation engine is meant to become a **generic compliance/rules engine**, usable by
other projects beyond `compliance-store` — not a `compliance-store`-specific module that
happens to live at a different network address. That reuse goal drives the scope decisions
below: the engine must own enough (evaluation *and* the rule catalog) to be a coherent,
self-contained product on its own, not a component that only works when paired with
`compliance-store`'s database.

---

## Decision

The regulation engine — the stateless ternary evaluator (`reg-engine-core`, lot 1a), the
simulation operation built on it (`reg-engine-simulation`, lot 1b), **and the rule catalog**
(`reg-catalog`, lot 2: attributes, frameworks, roles, lock types, versioned rules) — is
extracted into an external, standalone application, outside `compliance-store`, in Go.
`compliance-store` consumes it as a **provider**, through a dedicated gateway interface
(`RegulationEngineGatewayInterface`), following the same Provider Extension Port shape ADR
0001 already defines — but applied here to what ADR 0001 called the native core, which this
ADR revises.

### 1. Built generic, `compliance-store` is one tenant among others

The engine's public contract MUST NOT speak `compliance-store`'s vocabulary (documents,
storage classes tied to this project's infrastructure). It speaks in the domain terms
already used in `spec-v2.md` — subject, rule, lock type, regulatory framework, role — which
are generic by construction (§1 of `spec-v2.md`: "aucun régulateur, rôle, verrou ni classe
de stockage n'est codé en dur"). `compliance-store` maps its own concepts (a document, a
storage space) onto that vocabulary at the gateway boundary; the engine never needs to know
what a "document" is. Multi-tenancy (isolating one consumer project's catalog from
another's) is a concrete requirement of this constraint, not a nice-to-have — see open
question 2 below.

### 2. Fail-closed is non-negotiable

ADR 0001's additivity rule required a provider to never weaken the native core. Moving the
evaluator itself out does not relax that guarantee: if the external RegEngine is
unreachable, times out, or returns a malformed response, `compliance-store` MUST treat the
evaluation as **blocked**, never as an implicit pass. Unavailability of the regulation
engine is a compliance incident, not a bypass.

### 3. Scope — evaluation and catalog; document-bound lots stay open

This ADR moves lots 1a, 1b and 2 (evaluator + catalog) external. It does *not* decide where
lots 3–4 live:

* `reg-document-attributes` (lot 3) — document-scoped attribute *values*, referencing the
  external catalog by identifier.
* `reg-execution` (lot 4) — document state, purge, immutable audit trail.

Both touch document storage and envelope encryption (ADR 0002) directly, which argues for
keeping them native to `compliance-store` even though the catalog they reference lives
externally. Not decided here.

### 4. Contract, not implementation, crosses the boundary

`RegulationEngineGatewayInterface` exposes the evaluator's inputs/outputs (the C1–C8
contract already drafted for `reg-engine-simulation`) **and** catalog read/administration
operations — not the engine's internals. Transport (HTTP/gRPC), versioning, and
authentication between a consumer project and the engine are a design decision for the
gateway's implementation, not for this ADR.

---

## Relationship to ADR 0001

ADR 0001 remains internally consistent for every other compliance rule in
`compliance-store` — envelope encryption, retention, PII masking are still native-core
concerns, and the Provider Extension Port still means "optional, additive, vendor" for
those. This ADR carves out a **named exception**: regulatory rule *evaluation and catalog*
specifically are no longer classified as native core.

Following the project's convention (ADR 0010/0011), **ADR 0001's status is not edited
here.** Once this ADR is accepted, ADR 0001 should be marked `Superseded by ADR 0012` for
its rule-classification table only — that edit is left for the gate, not done silently.

---

## What this ADR does not authorize

* Moving envelope encryption, retention enforcement, or PII masking (ADR 0001's other
  native-core examples) out of the domain — those are untouched.
* An implicit pass when the external engine is unreachable (condition 2).
* Baking any `compliance-store`-specific concept into the engine's public contract
  (condition 1).
* A decision on where lots 3–4 live (condition 3).

---

## Open questions for Lionel (to close before `reg-engine-core` design resumes)

1. **Multi-tenancy shape.** If other projects will use the same engine, how is one
   consumer's catalog kept separate from another's — a tenant identifier on every call, a
   deployment per consumer, something else? This is now load-bearing given the reuse goal,
   not a detail.
2. **Failure-mode detail.** Timeout budget, retry policy, and what "blocked" surfaces as to
   the caller of `reg-engine-simulation` — a 422, a 503, something else. *(Still open —
   Lionel: "je ne sais pas encore".)*
3. **Versioning and release discipline** for a contract now shared across independent
   consumer projects, not just `compliance-store` and one external service — a breaking
   change on the engine side has a blast radius beyond this repo.

---

## Consequences

### Positive

* Matches the stateless shape already chosen for lot 1b (`reg-engine-simulation`) — no
  architectural surprise there, just a change of address.
* Decouples the evaluator's and catalog's release cadence and language from
  `compliance-store`.
* **Reusable beyond this project**, which was the actual goal — a generic engine is a more
  valuable artifact than a `compliance-store`-only module.

### Negative

* **Reopens `reg-engine-core`'s design.** It was being conceived (G2, `archi-guardian`) on
  the native-core assumption; that work restarts against this ADR once accepted, now also
  carrying the catalog (lot 2) instead of deferring it.
* **New failure mode.** A network dependency now sits on the path of every regulatory
  decision; condition 2 bounds the risk but does not remove the operational burden (health
  checks, timeouts, on-call surface).
* **Genericity is a discipline, not a default.** Every design choice on the engine side must
  be checked against "does this assume `compliance-store`," which is a standing review cost
  a project-embedded module wouldn't have carried.
* **Cross-repository, cross-consumer contract to maintain.** The C1–C8 contract plus the
  catalog API must now be versioned for multiple independent consumers, not just kept in
  sync between two modules in one codebase.
