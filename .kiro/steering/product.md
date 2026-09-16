---
inclusion: always
---

# Product

**Compliance Store** is an open-source REST API that manages the lifecycle of sensitive
documents — identity cards, passports, proofs of address — for organisations that must
collect them under a regulation (KYC, GDPR, sector-specific rules).

It is meant to be a **dedicated service**: business applications delegate sensitive
storage to it instead of re-implementing encryption, retention and proof of compliance
in their own database.

## Target users

Product and engineering teams that need compliant storage of sensitive documents without
carrying the security and regulatory burden themselves, and the compliance teams that
configure the rules applied to those documents.

## Founding principles

| Principle | What it means in the code |
| :--- | :--- |
| **Zero Trust by construction** | Envelope encryption: every document has its own data key, itself encrypted. The storage backend never sees plaintext (ADR 0002). |
| **Storage-agnostic** | S3-compatible backends through Flysystem; where the encrypted bytes live is an infrastructure detail. |
| **Multi-application** | A `StorageSpace` isolates each client application sharing the same instance. |
| **Configurable compliance, not hard-coded** | Retention, purge and applicable rules vary by context without redeployment. The rule engine's grammar is closed; its vocabulary is configurable (ADR 0001). |
| **Fail-closed** | When in doubt — missing data, invalid value, unresolvable rule — the system never performs an irreversible action by default. |
| **API-first** | Every capability is exposed through API Platform. |

## What exists today

Encrypted document storage, health check and `StorageSpace` management. The live
inventory is the code itself: `src/` and the API documentation at `/api/docs`.

## Feature specs

Each feature is specified in `.kiro/specs/<feature>/` (`requirements.md` in EARS notation,
`design.md`, `tasks.md`). A spec is the contract for its implementation: do not add
behaviour it does not require, and do not diverge from it silently — raise the point in
the pull request.
