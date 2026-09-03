# ADR 0009: Compliance Relevance of Document's Value Objects

* **Status**: Accepted
* **Date**: 2026-08-25
* **Authors**: Architecture Team
* **Project**: Compliance Store

---

## Context

`Document` (`src/Domain/Entity/Document.php`) carries five properties:

```php
final class Document
{
    private function __construct(
        private readonly DocumentId $id,
        private readonly FileHash $fileHash,
        private readonly WrappedDataKey $wrappedDataKey,
        private readonly StorageKey $storageKey,
        private readonly \DateTimeImmutable $createdAt,
    ) {}
}
```

Compliance Store's purpose is to let a compliance team audit the storage conditions of a document and its overall compliance (encryption at rest, integrity, traceability, retention). Not every field on `Document` serves that purpose: some are pure storage/encryption plumbing needed to operate the system, with no informational value to an auditor — and in one case, actively risky to expose.

This ADR classifies each of `Document`'s five fields against that audit need, and states the minimum set of information a compliance audit view should ever surface.

---

## Analysis grid

Four questions applied to each field:

| # | Question |
|---|---|
| Q1 | Does this field answer a question a compliance auditor would actually ask (confidentiality, integrity, traceability, retention, storage location)? |
| Q2 | Is it the **value itself** that matters to the audit, or only the **fact that it exists** (e.g. "encrypted: yes", without the key)? |
| Q3 | Does the field change if the underlying technology changes (storage backend, cipher library)? If so, it's an implementation detail, not compliance data. |
| Q4 | Does exposing the raw value in an audit view/report create a security risk? |

---

## Decision — field by field

### `DocumentId` — compliance-relevant

- Q1: yes — a stable reference an auditor uses to point at *this* document in an audit trail or report.
- Q2: the value itself is what's needed (it's a reference).
- Q3: no — stable regardless of storage/cipher backend.
- Q4: none — an opaque identifier, not a secret.

**Verdict**: part of the minimum compliance information set. Expose as-is.

### `FileHash` — compliance-relevant

- Q1: yes — the evidentiary proof that a document has not been altered since it was stored (integrity/non-repudiation), a core compliance-audit concern.
- Q2: the value itself matters — it's what lets the hash be recomputed and compared later.
- Q3: tied to one algorithm (SHA-256) today, but the underlying compliance concept ("integrity is verifiable") holds regardless of which digest algorithm is used.
- Q4: none — a digest cannot be reversed into content.

**Verdict**: part of the minimum compliance information set. Expose as-is.

### `createdAt` — compliance-relevant

- Q1: yes — the reference point for retention-period calculations and the timestamp an audit trail needs.
- Q2: the value itself matters.
- Q3: no.
- Q4: none.

**Verdict**: part of the minimum compliance information set. Expose as-is.

### `WrappedDataKey` — purely technical, out of compliance scope

- Q1: the underlying compliance question ("is this document encrypted?") is legitimate, but the wrapped key's **value** answers nothing an auditor needs — it proves nothing beyond a plain yes/no.
- Q2: only its *existence* carries meaning (envelope encryption was applied, per ADR 0002); the value itself adds nothing.
- Q3: entirely implementation-dependent (AEAD algorithm, KMS/master key currently in use) — cipher-layer plumbing.
- Q4: **yes, real risk** — surfacing the wrapped key in any report or view reachable by people who don't operate the cipher gateway is an unnecessary attack surface, even though it's only decryptable via the master key.

**Verdict**: purely technical. Required on the aggregate to allow decryption later (ADR 0002), but its raw value must never appear in a compliance-facing view or report. If a "document is encrypted" fact is ever needed for an audit, it should be expressed as a derived boolean/attestation — not by exposing `WrappedDataKey` itself. Note that today every `Document` is constructed with a `WrappedDataKey` unconditionally (it's a required constructor argument), so "encryption applied" is currently a structural guarantee of the system, not a per-document fact that needs its own field.

### `StorageKey` — purely technical, and insufficient for the real compliance question

- Q1: the underlying compliance question would be "where is this document physically stored?" (data residency / jurisdiction) — legitimate. But the current value of `StorageKey` (`"documents/{id}"`) does not answer it: it's an object path inside one bucket, not a region, provider, or jurisdiction indicator.
- Q2: the raw value gives an auditor nothing — it's internal addressing for the storage backend.
- Q3: 100% dependent on the storage backend (MinIO today, could be another S3-compatible tomorrow) — pure implementation detail.
- Q4: no direct security risk in exposing it, but no informational value either.

**Verdict**: purely technical, and — as currently modeled — unable to support a data-residency audit even if one were needed, since no field on `Document` captures storage location/jurisdiction at all today.

---

## Minimum compliance-audit information set

Based on the above, the minimum set of `Document` fields relevant to a compliance audit of storage conditions is:

| Field | Why it matters to compliance |
|---|---|
| `DocumentId` | Traceability — the reference used in an audit trail |
| `FileHash` | Integrity — proof the content hasn't been altered since storage |
| `createdAt` | Retention — start of the applicable retention period |

`WrappedDataKey` and `StorageKey` are excluded from that set: they are storage/encryption plumbing required for the system to operate, not information an audit needs to see. `WrappedDataKey`'s raw value must never be surfaced outside the cipher gateway's own machinery.

**Gap, noted but not addressed here**: today, nothing on `Document` captures *where* it is stored at a compliance-meaningful granularity (region, provider, jurisdiction). If data-residency auditing becomes a real requirement, it would need a new, dedicated piece of information — not a repurposing of `StorageKey`, whose value is an implementation-specific object path. Designing that is out of scope for this ADR.

---

## Consequences

### Positive
* A clear, field-level answer to "what does compliance need to see on a Document?" — useful when designing any future audit/compliance-facing view or API resource.
* Explicitly flags `WrappedDataKey` as a value that must never leak into audit-facing surfaces, ahead of any such surface being built.

### Negative
* None of this ADR's classification is enforced by code yet (no compliance-facing DTO/resource exists today to apply it to) — it's a decision to guide the next piece of work that builds one, not a completed implementation.
</content>
