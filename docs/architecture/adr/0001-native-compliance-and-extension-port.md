# ADR 0001: Native Compliance Core and Provider Extension Port

* **Status**: Obsolete
* **Date**: 2026-07-24
* **Authors**: Architecture & Compliance Team
* **Project**: Compliance Store

> **Obsolescence note (2026-08-21)**: `RegulatoryScope`, `DocumentComplianceChecker` and `RegulatoryScopeValidator` — the Native Compliance Core this ADR describes — have been removed. `StoreDocumentUseCase` no longer checks a document against any regulatory scope before storing it. This decision no longer reflects the codebase.

---

## Context

In projects handling sensitive documents (ID cards, passports, proof of address), regulatory compliance (GDPR, retention periods, PII masking) is often scattered or blindly delegated to external services (e.g. Onfido, Veriff) or plain S3 buckets.

This approach carries two major risks:
1. **Anemic Domain**: Compliance Store would become a simple pass-through proxy with no business intelligence of its own.
2. **Vendor Lock-in**: Impossible to switch verification providers without rewriting the application.

---

## Decision

We decide to adopt an **additive composition strategy** based on Clean Architecture:

1. **Native Compliance Core (in the Domain)**:
   * The business domain carries a **native compliance core**, fully configurable by the compliance team (without code deployment).
   * This core guarantees hard legal invariants (maximum retention period, mandatory encryption, PII masking by default).

2. **Provider Extension Port (`ExternalComplianceGatewayInterface`)**:
   * The domain exposes an extension port allowing optional external providers to be plugged in (e.g. Onfido, Veriff, external KYC).

3. **Additivity Rule ("Fail-Closed")**:
   * A document must validate **both** the native core **AND** the external provider's rules.
   * The external provider can **never** weaken the security or rules of the native core, it can only reinforce them.

---

## Rule Classification Heuristic

| Origin of the change | Location of the rule | Example |
| :--- | :--- | :--- |
| Legal / GDPR / regulatory change | **Domain (Native Core)** | Reducing a passport's retention period from 5 to 3 years. |
| Partner / technical vendor change | **Infrastructure (Provider)** | Additional fraud-score check from Onfido. |

---

## Consequences

### Positive
* **Compliance team autonomy**: Ability to evolve the native core dynamically.
* **Reinforced security**: Hard business rules are protected against failures or lax configuration of third-party providers.
* **Technical independence**: Switching external providers has no impact on the domain.

### Negative / Challenges
* Requires building a declarative rules engine (e.g. based on `Symfony ExpressionLanguage`) in the Domain layer.
