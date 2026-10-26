# Changelog

All notable changes to the **Compliance Store** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security
- Every endpoint but `GET /v1/health` and the OpenAPI documentation requires an API key (`Authorization: Bearer cs_...`). Keys belong to `ApiClient` records, are stored as SHA-256 hashes and are issued once by `app:api-client:create`, revoked by `app:api-client:revoke`. Roles `ROLE_ADMIN` > `ROLE_OPERATOR` > `ROLE_READER` are declared on each operation (ADR 0013).
- `POST /v1/documents` enforces `DOCUMENT_MAX_SIZE` (10M) and `DOCUMENT_MIME_TYPES` (PDF, PNG, JPEG, sniffed from the content); PHP limits raised to 12M/16M and Caddy answers 413 above 16 MiB before PHP runs.
- `GET /v1/documents/{id}/content` checks the decrypted content against the hash recorded at store time before releasing it; the response is `no-store`.
- Production Caddyfile: the Mercure hub is named, so a `SERVER_NAME` with several hosts no longer prevents the server from starting.

### Added
- GDPR erasure workflow: `POST /v1/holders/{id}/erasure-request` and `POST /v1/holders/{id}/anonymization` (administrators). Anonymization destroys the holder's documents (ciphertext and wrapped key) and drops the name and birth date; the holder then refuses new documents (422).
- Document reads: `GET /v1/documents/{id}` (metadata), `GET /v1/holders/{holderId}/documents` (oldest first), `GET /v1/documents/{id}/content` (decrypted download, `application/octet-stream`, ADR 0011).
- Audit trail: `AuditEvent` records holder registration, erasure request, anonymization, document storage and consultation with the acting API client; `GET /v1/audit-events?subjectId=&limit=` (administrators).
- `StoreDocumentUseCase` removes the ciphertext it just wrote when the database insert fails (no orphan object).
- Migrations `Version20261024093000` (api_client), `Version20261024100000` (nullable birth_date), `Version20261024110000` (audit_event).
- `Holder` aggregate persisted in full (`birth_date`, `status`, GDPR timestamps) with a dedicated `birth_date` Doctrine type; `GET /v1/holders/{id}` exposed through `GetHolderUseCase` and `GetHolderProvider`.
- `Document.holderId` persisted (`holder_id` column, indexed); `StoreDocumentUseCase` rejects an unknown holder with `HolderNotFoundException` (422 on `POST /v1/documents`).
- `Document` entity and Value Objects (`DocumentId`, `FileHash`, `EncryptedPayload`, `WrappedDataKey`, `EncryptionEnvelope`); `StorageKey` lives in `Infrastructure\Storage`.
- `StorageSpace` aggregate: `POST /v1/storage-spaces`, `GET /v1/storage-spaces`, `GET /v1/storage-spaces/{id}`, `PATCH /v1/storage-spaces/{id}` (status change).
- `IdentityDocument` aggregate: `POST /v1/identity-documents` with uniqueness on type + country + number.
- `GET /v1/health` reports the real kernel environment and the API version (both bound in `services.yaml`); covered by `HealthCheckApiTest`.
- `SodiumCipherGateway` implementation for Libsodium envelope encryption.
- `MinioStorageGateway` implementation for S3-compatible document storage (Flysystem).
- `StoreDocumentUseCase` exposed as `POST /v1/documents` (multipart upload).

### Removed
- `RegulatoryScope` feature (entity, value objects, use cases, repository, API resource, `DocumentComplianceChecker` domain service and `NonCompliantDocumentException`): never released, dropped before its first stable version.

### Changed
- Dependencies: Symfony 8.1.9, Doctrine ORM 3.7.5 and DBAL 4.5.0, PHPStan 2.3.1, PHPUnit 13.4.3, Deptrac 4.7.2. API Platform stays on 4.3. Added `symfony/security-bundle`, `symfony/expression-language`, `symfony/mime`.
- `Holder::anonymize()` takes no argument: the name becomes `anonymized` and the birth date `null`; `HolderRepositoryInterface::update()`, `DocumentRepositoryInterface::findById()/findByHolder()/remove()` and `StorageGatewayInterface::delete()` added.
- API tests authenticate through the `ApiClientFixture` trait; `tests/http` requests send `{{apiKey}}` from `http-client.private.env.json`.
- `HolderRepository` moved to `Infrastructure\Persistence\Doctrine` next to the other Doctrine repositories.
- `HolderName` and `StorageSpaceName` store the trimmed value; the four id Value Objects reject an empty string even without a validator.
- `StoreDocumentUseCase` validates the holder id with `IdValidatorInterface` like the other use cases.
- `StorageSpaceRepository::update()` uses DQL, fails with `StorageSpaceNotFoundException` when no row matches and no longer clears the whole unit of work.
- Doctrine custom types throw `InvalidType` on unexpected values instead of silently returning `NULL`.
- `GET /v1/storage-spaces` no longer advertises pagination parameters it does not honour; `DocumentResource::$file` is input-only.
- Tooling: PHPUnit `unit` and `api` suites, `make vendor` keeps dev dependencies, `make migrate-all` no longer generates an empty migration, `make test-db` validates the schema, Flysystem constraints bounded, `ext-sodium`, `symfony/validator` and `symfony/property-info` declared.
- Docker: `dunglas/frankenphp` pinned to `1.13.3-php8.5`; PostgreSQL and MinIO ports published in `compose.override.yaml` only; CI static analysis runs on PHP 8.5.

### Fixed
- `POST /v1/documents` answered 500 since `StoreDocumentCommand` requires a `holderId`: the processor now reads a `holderId` multipart field and validates it (`NotBlank`, `Uuid`).
- `BirthDate::fromString()` rejects malformed or auto-corrected dates with a 422 instead of a `TypeError`.
- `HolderRepository` was bound to `StorageSpace`; it now targets `Holder` and looks up by `HolderId`.
- `GeneratorInterface` declares `generate(): string`; `HolderProcessor` validates its inputs and maps domain exceptions to 409/422.
- Development stack: `Caddyfile.dev` aligned with Mercure >= 0.21 (named hub, `issuer` block, no `demo` directive) and `doctrine/dbal` bumped to 4.5.0 so `doctrine:schema:update` works with ORM 3.6.9.
- Production `Caddyfile`: same Mercure `issuer` block (the old root-level `publisher_jwt` made the image exit at start-up); dev-only `tls internal` and `auto_https disable_redirects` removed.
- `SodiumCipherGateway::decrypt()` turns truncated or malformed blobs into `RuntimeException` instead of an unhandled `SodiumException`.
- `BirthDate` and `IdentityDocument::create()` compare calendar days in UTC whatever the caller's timezone.
- `MinioStorageGateway::store()` no longer copies the adapter message (endpoint, bucket) into the exception message.
- `tests/http`: requests targeting non-existent endpoints removed, `store-document.http` sends the `holderId` part, `holders/post.http` chains POST then GET.
- Documentation: dead links in `README.md` and `docs/README.md`, ADR 0009 stray closing tag, ADR 0008 typos, architecture tree in `docs/architecture/overview/index.md`.

### Security
- `DOCUMENT_MASTER_KEY`, `AWS_S3_ACCESS_KEY` and `AWS_S3_SECRET_KEY` have no default in `compose.yaml` any more: dev values live in `compose.override.yaml`, `compose.prod.yaml` refuses to start without them (and without `POSTGRES_PASSWORD`).
- `WrappedDataKey` and `EncryptedPayload` mask their secret in `var_dump()`/`print_r()` output.
- `deploy.yml` and `labels.yml` check out the repository with `persist-credentials: false`.

### Planned
- `ConsultDocumentUseCase` (`GET /v1/documents/{id}`).

## [0.1.0] - 2026-07-24

### Added
- Symfony 8.1 & API Platform 4.3 skeleton with FrankenPHP and Caddy runtime.
- Docker Compose configuration featuring PostgreSQL 16 database and MinIO local S3 storage.
- Clean Architecture & DDD folder layout under `src/` (`Domain`, `Application`, `Infrastructure`, `Domain/Port/Gateway`).
- Comprehensive architecture documentation and ADRs (ADR 0001, ADR 0002, ADR 0003).
- Initial project configuration (`composer.json`, `README.md`).
