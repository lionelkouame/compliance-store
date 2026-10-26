---
slug: priority-fixes
title: "One day of fixes: from a broken environment to a secured API"
authors: [hiro]
tags: [epreuves, aujourdhui]
---

On 24 October 2026 the project was picked up on a fresh machine. The PHP container was restarting in a loop, the test database was empty and PHPStan counted nine errors. By the end of the day, the API required a key for every call, refused oversized or disguised files, could erase a person down to the destruction of their encryption keys, served document downloads with an integrity check and kept an audit trail. Here is how.

{/* truncate */}

## Morning: make what existed run

Three obstacles before writing a single line of business code.

**The PHP container would not start.** The FrankenPHP image shipped a recent Mercure version that no longer accepted two directives of the `Caddyfile.dev`. The file was fixed and aligned with the upstream template, and the container stayed up.

**The test database was empty.** `doctrine:schema:update` failed because of an incompatibility between Doctrine DBAL 4.4 and the Symfony 8.1 bridge, which required DBAL 4.5. One targeted upgrade later, 29 failing tests were green again.

**PHPStan counted nine errors**, all around the `Holder` aggregate and `DocumentProcessor`. Fixing them revealed that the holder was not fully persisted: the birth date was missing from the database. One migration later, the entity and its table said the same thing.

## Afternoon: an audit, then five structural weaknesses

Once the tooling was healthy, every folder of the project was reviewed. About sixty small fixes came out of it (secrets in Compose, production Caddyfile, Doctrine types, documentation). Above all, five structural weaknesses, handled in order of severity.

### 1. Anyone could do anything

No authentication at all. For a service that stores identity documents, that was the top priority. The choice, recorded in ADR 0013: **Bearer API keys** of which only the SHA-256 hash is kept, three hierarchical roles (`ROLE_READER`, `ROLE_OPERATOR`, `ROLE_ADMIN`), every operation declaring the role it requires. Two console commands create and revoke keys. No key: 401. Insufficient role: 403.

### 2. A file of any size and any type

Three barriers now: Caddy rejects bodies above 16 MiB before PHP reads them, PHP caps uploads, and the application checks the configured size and the **real type** of the file by inspecting its content. A text file renamed to `.png` is refused with an explicit 422 error.

### 3. A right to erasure that did not really apply

The entity knew how to change status, but nothing was persisted and the documents stayed in place. Now two administrative operations: the erasure request, then the anonymisation that deletes every encrypted file from storage, clears its key from the database, replaces the name with `anonymized` and empties the birth date. This is **cryptographic shredding**: even a residual copy of the encrypted file would be unreadable.

### 4. A file written before its database row

If the insert failed after the upload to storage, an orphan file remained. The store use case now compensates the failure by deleting the object, then rethrows the original error.

### 5. An API that wrote but never read

A document could be stored, never read back. Added: metadata read, list per holder and **decrypted download**, which recomputes the SHA-256 fingerprint and refuses to deliver altered content. And an append-only **audit trail** that records who registered, stored, consulted, requested erasure of or anonymised what.

## Evening: write for the others

Every piece of work has its documentation in `docs/correctifs/` of the working repository: eight reports, a timestamped logbook, then a [plain-language overview](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/09-presentation-generale.md) and a [technical documentation](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/10-documentation-technique.md) (both in French). The main README was rewritten, and a checklist of what every team member must do after pulling the changes (rebuild the image, migrate, create their key) was added.

## The numbers

Deptrac 0 violation, PHPStan 0 error at maximum level, 159 PHPUnit tests and 381 assertions, all green, before every commit thanks to the pre-commit hook.

## What remains to decide

The work deliberately left open the decisions that belong to the project owner: expiry and rotation of API keys, master key rotation, rate limiting, retention and export of the audit trail, linking identity documents to a holder, a real role for storage spaces, document retention period. They are listed in the [priority fixes](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/08-corrections-prioritaires.md) report, section 8.8.
