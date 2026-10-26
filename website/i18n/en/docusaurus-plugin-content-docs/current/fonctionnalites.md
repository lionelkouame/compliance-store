---
sidebar_position: 2
---

# What the application does

This page explains the application to anyone who wants to understand it without reading the code. The detailed version, with the data model and the API reference, is the [technical documentation](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/10-documentation-technique.md) in the repository (in French).

## The problem it solves

Many organisations must keep copies of personal documents. These documents are **sensitive** (a leak exposes people to identity theft), **regulated** (the GDPR requires that they can be deleted and that access is known) and must stay **trustworthy** (one must prove they were not altered).

Compliance Store is a digital vault built for these three constraints.

## The picture to keep in mind

| In a bank | In Compliance Store |
|-----------|---------------------|
| A customer opens a file in their name | A **holder** (the data subject) is registered |
| They put a sealed envelope in a safe | A **document is stored**, encrypted before it reaches storage |
| The bank records the seal's imprint | The document's **digital fingerprint** is recorded |
| A key and the right clearance are needed to consult | An **API key** and a sufficient **role** are needed |
| Broken seal: the bank refuses to hand over the envelope | Altered document: the application **refuses to deliver it** |
| Every visit to the safe is logged in a register | Every action is written to the **audit trail** |
| The customer can have their file destroyed | The person exercises their **right to erasure** |

The difference with a bank: here, even the person who files the envelopes cannot read them. Storage never receives a document in clear text.

## The features, step by step

### Register a person

The **holder** is declared with a name and a birth date. They receive a unique identifier and the *active* status.

### Store a document

An authorised client sends a file (PDF, PNG or JPEG, 10 MB at most) and names the holder. The application checks that the holder is active, inspects the file's real type by looking at its content, computes its fingerprint, encrypts it with a key unique to this document, protects that key with a master key, then sends the encrypted file to storage. If the database write fails afterwards, the encrypted file is deleted so that nothing is left orphaned.

### Consult a document

With the right role, one reads a document's record, lists a holder's documents, or downloads the decrypted content. On download, the fingerprint is recomputed: if it differs from the one recorded at store time, the document is **not delivered**. Every download is logged with the requester's name.

### Erase (GDPR, article 17)

Two steps reserved to administrators:

1. **Erasure request**: the holder can no longer receive documents.
2. **Anonymisation**: their documents are destroyed (files and keys), the name becomes `anonymized`, the birth date is cleared. Only the identifier, the status and the dates remain, to prove that erasure happened.

### Trace everything

The audit trail records, with no possible update or deletion: holder registration, document storage, consultation, erasure request, anonymisation. Each line carries the action, the subject, the instant and **who** acted.

### Two side modules

- **Identity documents**: declaration of a passport or national card (type, country, number, expiry), no duplicates, no expired document.
- **Storage spaces**: logical locations to activate, deactivate or archive.

Both modules are currently **independent** from the rest (see the limits below).

## Who may do what

Nothing is possible without an **API key**. It is handed out once, and the application keeps only its hash: even by reading the database, nobody can recover it. A compromised key is revoked immediately.

| Role | Allows | Typically |
|------|--------|-----------|
| **Reader** | Reading holders, document records, storage spaces | Dashboard, reporting |
| **Operator** | All of the above, plus registering holders and identity documents, storing and downloading documents | Business back-office |
| **Administrator** | All of the above, plus storage spaces, GDPR erasure, audit trail | Data protection officer, operations |

Only the service's health status and the public description of the interface are reachable without a key.

## What protects the documents

- **Envelope encryption**: one key per document, all those keys locked by a master key that never leaves the application. Stealing the storage is useless without the database; stealing the database is useless without the master key.
- **Fingerprint**: a photo of the seal at store time. A single changed character and the application detects it.
- **Destruction by the key**: at anonymisation, file and key disappear. A residual copy would be unreadable forever.
- **Size and type limits**: an oversized or disguised file is refused before any processing.
- **Append-only trail**: it can only grow.

## What the application does not do yet

- No automatic retention period: erasure is always triggered by an administrator.
- Identity documents are linked neither to a holder nor to a file.
- Storage spaces do not yet influence where a document is written.
- API keys do not expire.
- No rate limiting.
- No graphical interface.

These points are listed as decisions to make in the [audit](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/07-audit.md) and [priority fixes](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/08-corrections-prioritaires.md) reports (in French).

## In one sentence

Compliance Store receives personal documents, makes them unreadable to anyone but the application itself, proves they were not altered, records who consults them, and knows how to make them disappear cleanly when the law requires it.
