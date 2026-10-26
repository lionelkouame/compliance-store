# Compliance Store API

[![CI](https://github.com/lionelkouame/compliance-store/actions/workflows/ci.yaml/badge.svg)](https://github.com/lionelkouame/compliance-store/actions/workflows/ci.yaml)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?logo=symfony&logoColor=white)
![API Platform](https://img.shields.io/badge/API%20Platform-v4.3-30B69E?logo=apiplatform&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![FrankenPHP](https://img.shields.io/badge/FrankenPHP-1.13-00A88F?logo=frankenphp&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-159%20tests-3776AB?logo=phpunit&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-4F5B93)
![Super-Linter](https://img.shields.io/badge/Super--Linter-Passing-2088FF?logo=githubactions&logoColor=white)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**Compliance Store** is an open-source REST API for the full lifecycle of sensitive documents (IDs, passports, utility bills): registration of the data subject, encrypted storage, controlled consultation, GDPR erasure, and an audit trail of every action.

Every document is protected by *Zero Trust* envelope encryption (Libsodium) before it reaches any S3-compatible backend (AWS S3, MinIO) through Flysystem. The storage never sees a plaintext byte, and the application never releases a decrypted document whose integrity it has not verified.

---

## ✨ What it does

| Capability | Details |
|------------|---------|
| **Holders** (data subjects) | Registration with name and birth date, lookup by id, lifecycle `active` → `erasure_requested` → `anonymized`. |
| **Encrypted documents** | Upload (PDF, PNG, JPEG, 10 MB by default), per-document data key wrapped with a master key, SHA-256 of the plaintext recorded at store time. |
| **Controlled consultation** | Metadata, list per holder, decrypted download that is refused if the content no longer matches its recorded hash. |
| **GDPR right to erasure** | Two-step workflow for administrators: flag the request, then destroy every document (ciphertext and wrapped key) and anonymize the record. No new document can be attached afterwards. |
| **Identity documents** | Registration of passports and national cards with uniqueness on type, country and number. |
| **Storage spaces** | Logical storage locations with a status lifecycle. |
| **Audit trail** | Append-only log of who registered, stored, consulted, requested erasure or anonymized what, filterable per subject. |
| **Access control** | Bearer API keys stored hashed, three hierarchical roles, every operation declares the role it requires. |

---

## 🏛️ Architecture & Design

The application is built with **PHP 8.5**, **Symfony 8.1** and **API Platform 4.3**, following **Clean Architecture** and **Domain-Driven Design**. The domain depends on nothing; Deptrac enforces the layering on every commit.

```text
src/
├── Domain/              # Pure business logic: no framework, no I/O
│   ├── Entity/          # Aggregates: Holder, Document, IdentityDocument, StorageSpace, AuditEvent
│   ├── ValueObject/     # Validated immutables: HolderId, BirthDate, FileHash, EncryptedPayload, AuditAction…
│   ├── Exception/       # Business exceptions (HolderNotFoundException, DocumentIntegrityException…)
│   └── Port/            # Interfaces the domain needs: Gateway/ (cipher, storage), Repository/, Service/ (ids, audit trail)
├── Application/
│   └── UseCase/         # One folder per use case (StoreDocument, ConsultDocument, AnonymizeHolder…) with its Command
└── Infrastructure/      # Technical implementations of the ports
    ├── Persistence/     # Doctrine repositories, XML mappings and custom DBAL types (PostgreSQL)
    ├── Presentation/    # API Platform resources, state providers and processors (one use case each)
    ├── Storage/         # MinIO / S3-compatible document storage (Flysystem)
    ├── Gateway/         # Libsodium envelope encryption
    ├── Security/        # API clients and bearer API key authentication (ADR 0013)
    ├── Audit/           # Audit trail adapter (actor = authenticated API client)
    ├── Console/         # app:api-client:create, app:api-client:revoke
    └── Service/         # UUID generators and validator
```

Detailed documentation:
* 🧭 [Compliance Store expliqué à tout le monde](docs/correctifs/09-presentation-generale.md) (plain-language overview, in French) and [Documentation technique](docs/correctifs/10-documentation-technique.md) (architecture, data model, API reference, operations, in French)
* 📘 [Documentation table of contents](docs/README.md)
* 📜 [Architecture Decision Records](docs/architecture/adr/) (13 ADRs, from envelope encryption to API key authentication)
* 🩹 [Documents correctifs](docs/correctifs/README.md): report of the 24 October 2026 interventions, logbook, team checklist (in French)

---

## 🚀 Quick Start (development)

The stack runs on **FrankenPHP** and **Caddy** with Docker Compose: PHP API, PostgreSQL 16, MinIO.

### Prerequisites

* Docker and Docker Compose v2.10+
* `make` (on Windows: Git Bash with `make`, or run the underlying `docker compose` commands shown by `make help`)

### 1. Local DNS

Map `compliance-store.loc` to `127.0.0.1`:
* **Linux / macOS**: `make setup-dns`
* **Windows**: add `127.0.0.1 compliance-store.loc` to `C:\Windows\System32\drivers\etc\hosts`

### 2. Build and start

```bash
make build      # builds the images (PHP ini and prod Caddyfile are baked in)
make up         # starts PHP + PostgreSQL + MinIO
make migrate    # applies the database migrations
```

`make` loads `.env.local` before calling Docker Compose; a bare `docker compose up` does not. Use that file to move published ports when they are taken on your host, for example `POSTGRES_PORT=5434`.

### 3. Create your API key

Every endpoint except `GET /api/v1/health` and the OpenAPI documentation requires an API key. Each person and each integration gets its own, with the lowest role that fits:

```bash
make sf c="app:api-client:create <your-name-or-service> --role=ROLE_READER"
```

The key (`cs_...`) is printed **once** and stored hashed. Send it on every request:

```http
Authorization: Bearer cs_...
```

| Role | Allows |
|------|--------|
| `ROLE_READER` | Reading holders, document metadata, storage spaces |
| `ROLE_OPERATOR` | Everything above, plus registering holders and identity documents, storing documents, downloading decrypted content |
| `ROLE_ADMIN` | Everything above, plus storage space management, GDPR erasure workflow, audit trail |

Revoke a key at any time: `make sf c="app:api-client:revoke <name>"`.

### 4. Use the API

* **Swagger UI**: `https://compliance-store.loc/api` (or `http://compliance-store.loc/api`). Click **Authorize** and paste your key.
* **HTTP request files**: `tests/http/` (JetBrains HTTP Client or VS Code REST Client). Copy `tests/http/http-client.private.env.json.dist` to `http-client.private.env.json` (ignored by Git) and paste your key. See [tests/http/README.md](tests/http/README.md).
* **MinIO console**: `http://localhost:9001` (`minioadmin` / `minioadmin`, development only).

### 5. Stop

```bash
make down
```

### Already have a checkout? Do this after pulling

These steps apply to **every** developer, integrator and operator:

1. `make build && make up`: the PHP upload limits live in `frankenphp/conf.d/10-app.ini`, which is baked into the image.
2. `make migrate`: new `api_client` and `audit_event` tables, nullable `holder.birth_date`.
3. Create your own API key (step 3 above) and paste it into `tests/http/http-client.private.env.json` or into Swagger UI.
4. `make test` must end with Deptrac 0 violation, PHPStan 0 error, PHPUnit 159 tests.

---

## 🔌 API overview (`/api/v1`)

| Endpoint | Role | Purpose |
|----------|------|---------|
| `GET /health` | public | Liveness, environment and version |
| `POST /holders` | operator | Register a data subject (`holderName`, `birthDate`) |
| `GET /holders/{id}` | reader | Read a holder (anonymized holders keep id, status and timestamps only) |
| `POST /holders/{id}/erasure-request` | admin | GDPR art. 17, step 1: flag the request (409 if not active) |
| `POST /holders/{id}/anonymization` | admin | Step 2: destroy the holder's documents and anonymize the record (409 without a pending request) |
| `POST /documents` (multipart `file` + `holderId`) | operator | Encrypt and store a document; 422 above `DOCUMENT_MAX_SIZE` or outside `DOCUMENT_MIME_TYPES`, 413 above 16 MiB |
| `GET /documents/{id}` | reader | Metadata: holder, SHA-256, date |
| `GET /holders/{id}/documents` | reader | Documents of a holder, oldest first (404 for an unknown holder) |
| `GET /documents/{id}/content` | operator | Decrypted download, `Accept: application/octet-stream` required (406 otherwise), integrity-checked, audited |
| `POST /identity-documents` | operator | Register a passport or national card |
| `GET /storage-spaces`, `GET /storage-spaces/{id}` | reader | Storage spaces |
| `POST /storage-spaces`, `PATCH /storage-spaces/{id}` | admin | Create a storage space, change its status |
| `GET /audit-events?subjectId=&limit=` | admin | Who did what to which holder or document, newest first |

Errors follow RFC 9457 (`application/problem+json`): 401 missing or invalid key, 403 insufficient role, 404 unknown or malformed id, 409 invalid state transition or integrity failure, 422 validation.

---

## ⚙️ Configuration

Set through environment variables (`.env` holds development defaults, `compose.prod.yaml` makes the secrets mandatory).

| Variable | Purpose | Default (dev) |
|----------|---------|---------------|
| `DOCUMENT_MASTER_KEY` | Base64 of 32 random bytes; wraps every document's data key. **Never commit a production value.** | dev value in `compose.override.yaml` |
| `DOCUMENT_MAX_SIZE` | Maximum upload size (Symfony notation); keep below `upload_max_filesize` (12M) and the Caddy limit (16 MiB) | `10M` |
| `DOCUMENT_MIME_TYPES` | Accepted types, detected from the file content | `application/pdf,image/png,image/jpeg` |
| `AWS_S3_ENDPOINT`, `AWS_S3_BUCKET`, `AWS_S3_ACCESS_KEY`, `AWS_S3_SECRET_KEY` | S3-compatible storage of the ciphertexts | MinIO of the Compose stack |
| `DATABASE_URL`, `POSTGRES_PASSWORD` | PostgreSQL | Compose stack |
| `SERVER_NAME`, `HTTP_PORT`, `HTTPS_PORT` | Caddy hosts and published ports | `compliance-store.loc, localhost`, 80, 443 |
| `CORS_ALLOW_ORIGIN` | Allowed browser origins | localhost |

Generate a master key: `php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'`.

---

## 🧪 Development workflow

| Command | What it does |
|---------|--------------|
| `make test` | Deptrac + PHPStan (level max) + database resync + PHPUnit (`unit` and `api` suites). Also run by the pre-commit hook (`make setup-hooks`). |
| `make test c="--testsuite unit"` | Unit tests only, no database needed |
| `make phpstan`, `make deptrac` | Static analysis, architecture layers |
| `make migration-diff`, `make migrate` | Generate a migration from the mapping, apply pending migrations |
| `make sf c="..."`, `make composer c="..."` | Run a Symfony console or Composer command in the container |
| `make logs`, `make bash` | Follow the logs, open a shell in the PHP container |

Quality gates: Deptrac 0 violation, PHPStan 0 error, PHPUnit 159 tests / 381 assertions. The GitHub Actions workflow runs the same checks plus Super-Linter.

---

## 🛠️ Technology stack

* **Runtime**: PHP 8.5 on FrankenPHP 1.13 (worker mode) with Caddy
* **Framework**: Symfony 8.1, API Platform 4.3, Symfony Security (access-token authenticator)
* **Persistence**: PostgreSQL 16, Doctrine ORM 3.7 with XML mappings and custom DBAL types for value objects
* **Object storage**: MinIO / any S3-compatible service through League Flysystem
* **Cryptography**: Libsodium `sodium_crypto_secretbox` (envelope encryption), SHA-256 integrity, SHA-256 hashed API keys
* **Quality**: PHPStan level max, Deptrac, PHPUnit 13, Super-Linter

---

## 📖 Underlying template

This project is built upon the [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker) template.
For the original Docker/FrankenPHP infrastructure documentation, see [README.symfony-docker.md](README.symfony-docker.md) or the [docs/docker-infra/](docs/docker-infra/) directory (production deployment, TLS, Xdebug, troubleshooting).

---

## 📄 License

This project is released under the MIT License.
