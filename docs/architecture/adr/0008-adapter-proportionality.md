# ADR 0008: Adapter Proportionality — Dedicated Adapters Only Where They Isolate Real Volatility

* **Status**: Accepted
* **Date**: 2026-08-21
* **Authors**: Architecture Team
* **Project**: Compliance Store

---

## Context

`DocumentResource` (`src/Infrastructure/ApiPlatform/Resource/V1/DocumentResource.php`) carries two static named constructors: `fromEntity(Document $document): self` (Domain → HTTP DTO, output direction) and `fromRequest(?Request $request): self` (raw HTTP request → HTTP DTO, input direction, required because the `Post` operation declares `deserialize: false`). Both are simple field-by-field mappings with a single caller (`DocumentProcessor`) and no substitution need.

This raised a question of principle: should every DTO ↔ Domain conversion in this codebase go through a dedicated Adapter (an interface plus an injectable implementation), for consistency, even when the mapping is trivial and has a single call site? Or is a static named constructor on the DTO itself an acceptable default, with a dedicated Adapter reserved for cases that actually need one?

The tension: a single rule applied everywhere is easier to teach and to enforce in review than case-by-case judgment. But the Adapter pattern (Ports & Adapters) exists to isolate volatility — to let one thing be substituted for another (a different implementation, a test double, a different data source) without touching its caller. `DocumentProcessor` is already the real substitution point in this flow: it implements `ProcessorInterface` and is swapped/mocked at that boundary, per ADR 0003 ("Decoupling API Platform from Clean Architecture via State Processors / Providers"), which already qualifies API Exposure Resources as "lightweight DTOs". Wrapping a three-line field mapping in an interface with one implementation, when nothing is being substituted, adds a file and an indirection to read without adding testability or flexibility that wasn't already there.

## Decision

A dedicated Adapter (interface + injected implementation) is justified when **at least one** of the following is true:

* There are multiple real or near-term implementations of the conversion (not hypothetical future ones).
* The conversion needs to be substituted independently of its caller in a unit test.
* The conversion logic depends on an external resource (I/O, a third-party service) rather than a plain field mapping.

Otherwise, a static named constructor on the DTO itself (`fromEntity`, `fromRequest`, or similar) is the default choice. This matches the existing `DocumentResource` pattern and ADR 0003's framing of Resources as lightweight DTOs — the DTO owning its own construction from an adjacent representation is within its responsibility, not a layering violation.

## Consequences

### Positive
* No unjustified indirection: trivial mapping logic stays readable in one place, next to the type it constructs.
* The rule is explicit, which makes it easier to apply consistently in code review than open-ended judgment.

### Negative / Vigilance
* If a Resource accumulates several `fromX()` methods for heterogeneous sources (e.g. a v2 request shape, a different input channel), that is the signal to revisit and extract a dedicated Adapter at that point — the rule requires a periodic re-check rather than a decision frozen at creation time.
