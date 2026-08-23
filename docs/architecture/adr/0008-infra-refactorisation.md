# ADR 0008: Infrastructure Refactoring

* **Status**: Accepted
* **Date**: 2026-08-23
* **Author**: Lionel Kouamé
* **Project**: Compliance Store

---

## Context  

We want to review the infrastructure folder to follow better the convention of clean architecture (Separation of concerns).
The folder ApiPlatform is directly in the Infrasctructure root.
the  doctrine files is directtly in the persistence root folder.  
The folder Gateway contain the storage external service.
```
Infrastructure/
├─ ApiPlatform/
├─ Gateway/
├─ Persistence/
```

We want to go even further in the technical separation end description of each service.

## Decision

### Add the presentation folder.

- The presentation folder represent the presentation layer who emebeded all services you exepose the application to the outside word.

- The Persistence folder currently holds the Doctrine files directly. We want to add a Doctrine folder inside Persistence to better follow the clean architecture rules.

### Group API versioning by version, not by technical type.

- `ApiPlatform/Resource/V1` and `ApiPlatform/State/V1` duplicated the `V1` segment under each technical folder. The version is promoted to the top level instead: `ApiPlatform/V1/Resource` and `ApiPlatform/V1/State`. A future `V2` is added the same way, alongside `V1`, without touching existing code.
- `ApiPlatform/Validator` is not versioned (see below): validation rules are shared across API versions, not tied to one.

### Move Validator out of ApiPlatform and rename it Validation.

- `Infrastructure/Presentation/ApiPlatform/Validator` is moved to `Infrastructure/Validation`, at the root of `Infrastructure`, and renamed from `Validator` to `Validation`.
- These custom Symfony constraints (`AssertJurisdictionCodeUnique`, `AssertLegalFrameworkCodeUnique`, `AssertRegulatoryScopeCodeUnique`) validate domain uniqueness rules; they are consumed by API Platform resources today but are not an API Platform concern themselves, so they don't belong under `Presentation/ApiPlatform`.

### Extract a dedicated Storage folder from Gateway.

- `Infrastructure/Gateway` mixed two unrelated technical concerns under one folder: `MinioStorageGateway` (external document storage, implements `StorageGatewayInterface`) and `SodiumCipherGateway` (envelope encryption, implements `CipherGatewayInterface`).
- `MinioStorageGateway` is moved to `Infrastructure/Storage`, at the root of `Infrastructure`, following the same pattern as `Persistence`, `Presentation` and `Validation`: one root folder per technical concern.
- `SodiumCipherGateway` stays in `Infrastructure/Gateway` for now; `Gateway` is not renamed or removed since it currently still holds a distinct adapter.

## Consequences

### Positive
* **Explicit Presentation Boundary**: API Platform resources and state providers/processors now live under `Infrastructure/Presentation/ApiPlatform/V1`, making the outward-facing layer, and its versioning, immediately identifiable and separable from persistence or storage concerns.
* **Isolated Doctrine Concerns**: Repositories, XML mappings, and custom DBAL types are grouped under `Infrastructure/Persistence/Doctrine`, so the `Persistence` folder can host a non-Doctrine implementation in the future without restructuring existing code.
* **Version-First API Layout**: Grouping `Resource` and `State` under `V1` means adding a `V2` endpoint never requires touching `V1` folders, and a version can be deprecated/removed as one self-contained unit.
* **Framework-Neutral Validation**: `Infrastructure/Validation` is no longer nested under `ApiPlatform`, making explicit that these constraints validate domain rules and can be reused by any consumer (API Platform, a future CLI command, a message handler), not just the HTTP layer.
* **One Adapter, One Concern**: `Infrastructure/Storage` isolates the document storage adapter from `Gateway`, so `Storage` and `Gateway` each map to a single, unambiguous technical responsibility instead of `Gateway` bundling storage and encryption together.
* **Easier Onboarding**: The folder structure now mirrors the technical role of each service, reducing the need to open files to understand what a given class is responsible for.

### Negative
* Every moved class requires a namespace update, and every reference to those namespaces (config files, `use` statements, test suites) must be updated in the same change, increasing the size of the refactor diff.
* Framework configuration (`config/packages/doctrine.yaml` mapping `dir`, `config/packages/api_platform.yaml` mapping `paths`) is now coupled to the new folder depth and must be kept in sync if the structure changes again.
* `Gateway` is left with a single class (`SodiumCipherGateway`), which is an inconsistent end-state if the same one-folder-per-concern logic is applied to it later — a follow-up ADR may be needed to rename or fold it.
