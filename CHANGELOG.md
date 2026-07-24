# Changelog

All notable changes to the **Compliance Store** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Value Objects (`DocumentId`, `FileHash`, `EncryptedPayload`).
- `SodiumCipherGateway` implementation for Libsodium envelope encryption.
- `MinioStorageGateway` implementation for S3-compatible document storage.
- `StoreDocumentUseCase` (`POST /v1/documents`).

## [0.1.0] - 2026-07-24

### Added
- Symfony 7 & API Platform 3 skeleton with FrankenPHP and Caddy runtime.
- Docker Compose configuration featuring PostgreSQL 16 database and MinIO local S3 storage.
- Clean Architecture & DDD folder layout under `src/` (`Domain`, `Application`, `Infrastructure`, `Domain/Port/Gateway`).
- Comprehensive architecture documentation and ADRs (ADR 0001, ADR 0002, ADR 0003).
- Initial project configuration (`composer.json`, `README.md`).
