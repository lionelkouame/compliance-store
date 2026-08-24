# Compliance Store API

[![CI](https://github.com/lionelkouame/compliance-store/actions/workflows/ci.yaml/badge.svg)](https://github.com/lionelkouame/compliance-store/actions/workflows/ci.yaml)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.4-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?logo=symfony&logoColor=white)
![API Platform](https://img.shields.io/badge/API%20Platform-v4.3-30B69E?logo=apiplatform&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![FrankenPHP](https://img.shields.io/badge/FrankenPHP-Ready-00A88F?logo=frankenphp&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-Passing-3776AB?logo=phpunit&logoColor=white)
![Super-Linter](https://img.shields.io/badge/Super--Linter-Passing-2088FF?logo=githubactions&logoColor=white)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**Compliance Store** is a highly available, secure open-source REST API designed for managing the full lifecycle of sensitive document storage (IDs, passports, utility bills).

It provides *Zero Trust* envelope encryption (Libsodium) for every stored document and integrates with any S3-compatible storage backend (AWS S3, MinIO) via Flysystem.

---

## 🏛️ Architecture & Design

The application is built with **PHP 8.4+**, **Symfony 8.1**, and **API Platform 4**, strictly adhering to the principles of **Clean Architecture** and **Domain-Driven Design (DDD)**:

```text
src/
├── Domain/              # Pure business logic (Entities, Value Objects, Ports)
│   └── Port/            # Interfaces (Gateways, Repositories, Services)
├── Application/         # Orchestration & Use Cases (Use Cases & DTOs)
└── Infrastructure/      # Technical implementations
    ├── Persistence/     # Doctrine repositories, mappings & custom types (PostgreSQL)
    ├── Presentation/    # API Platform resources & state providers/processors
    ├── Storage/         # MinIO / S3-compatible document storage (Flysystem)
    ├── Gateway/         # Libsodium envelope encryption
    └── Service/         # UUID generators
```

To explore the detailed architecture documentation:
* 📘 [Documentation Table of Contents](docs/README.md)
* 📜 [Architecture Decision Records (ADRs)](docs/architecture/adr/)
* 🔄 [Ingestion & Consultation Workflows](docs/architecture/workflows/)

---

## 🚀 Quick Start (Development Environment)

The application runs on **FrankenPHP** and **Caddy** powered by Docker Compose.

### Prerequisites
* Docker & Docker Compose (v2.10+)

### 🌐 Local DNS Setup (`compliance-store.loc`)

Map `compliance-store.loc` to `127.0.0.1` on your development host:
* **Linux / macOS**: Run `make setup-dns` or execute `echo "127.0.0.1 compliance-store.loc" | sudo tee -a /etc/hosts`
* **Windows**: Add `127.0.0.1 compliance-store.loc` to `C:\Windows\System32\drivers\etc\hosts`

### Launching the Stack

1. **Build Docker images**:
   ```bash
   docker compose build --pull --no-cache
   ```

2. **Start all services** (FrankenPHP API + PostgreSQL + MinIO S3):
   ```bash
   docker compose up -d
   ```

3. **Access the application**:
   * **API Platform / Swagger UI**: `https://compliance-store.loc/api` (or `http://compliance-store.loc/api`)
   * **MinIO Web Console (Local S3)**: `http://localhost:9001` *(User: `minioadmin` / Pass: `minioadmin`)*

4. **Stop the environment**:
   ```bash
   docker compose down
   ```

---

## 🛠️ Technology Stack

* **Framework**: Symfony 8.1 + API Platform 4
* **HTTP Server / Runtime**: FrankenPHP + Caddy (Worker Mode)
* **Database**: PostgreSQL 16
* **Object Storage**: MinIO (S3-compatible) / League Flysystem
* **Cryptography**: Libsodium (`sodium_crypto_secretbox`)

---

## 📖 Underlying Template

This project is built upon the [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker) template.
For original Docker/FrankenPHP infrastructure documentation, refer to [README.symfony-docker.md](README.symfony-docker.md) or the [docs/docker-infra/](docs/docker-infra/) directory.

---

## 📄 License

This project is released under the MIT License.
