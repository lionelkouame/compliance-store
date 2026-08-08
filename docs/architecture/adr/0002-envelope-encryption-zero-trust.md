# ADR 0002: Envelope Encryption and Zero Trust Storage

* **Status**: Accepted
* **Date**: 2026-07-24
* **Authors**: Architecture & Security Team
* **Project**: Compliance Store

---

## Context

Documents stored by Compliance Store are highly confidential (ID cards, passports, payslips). The storage backend (MinIO, AWS S3, Azure Blob) must never receive or retain a document in plaintext (*Zero Trust Storage*).

Furthermore, if a document needs to be masked (PII redaction), this operation must happen on the plaintext binary **only in RAM**, before it is encrypted.

---

## Decision

1. **Systematic encryption before write (`CipherPort`)**:
   * No byte is sent to the `StorageGatewayInterface` without first being encrypted by the application via `sodium_crypto_secretbox` (or KMS/Vault).

2. **Envelope Encryption**:
   * Each document is encrypted with a unique Data Key.
   * The Data Key is itself encrypted by a Master Key (KMS).

3. **Placement in the Pipeline**:
   * Ingestion: `Reception` $\rightarrow$ `Compliance Check` $\rightarrow$ `PII Redaction / Masking (in memory)` $\rightarrow$ `Encryption (CipherPort)` $\rightarrow$ `Storage (StorageGateway)`.
   * Consultation: `Storage Read (Encrypted)` $\rightarrow$ `Decryption (in memory)` $\rightarrow$ `Dynamic Redaction (if partial access)` $\rightarrow$ `HTTP Response`.

---

## Consequences

### Positive
* **Total confidentiality**: Even if the S3 bucket or hard drives leak, data remains completely unreadable without the master KMS key.
* **Redaction preserved**: Allows applying regulatory redaction to the document before its final encryption.

### Negative
* Secure management and rotation of master keys (KMS / secured environment variables).
