# Compliance Store API

**Compliance Store** is a highly available, secure open-source REST API designed for managing the full lifecycle of sensitive document storage (IDs, passports, utility bills).

It provides fine-grained regulatory compliance management, *Zero Trust* envelope encryption, automated PII data masking/redaction, and integrates seamlessly with any storage provider (AWS S3, MinIO, Azure Blob, Local File System).

---

## 🏛️ Architecture & Design

The application is built with **PHP 8.3+**, **Symfony 7**, and **API Platform**, strictly adhering to the principles of **Clean Architecture** and **Domain-Driven Design (DDD)**:

```text
src/
├── Domain/              # Pure business logic (Entities, Value Objects, Native Compliance Rules)
│   └── Port/            # Interfaces (Gateways, Repositories, Events, Clock)
├── Application/         # Orchestration & Use Cases (Use Cases & DTOs)
└── Infrastructure/      # Technical implementations (MinIO, Sodium, PostgreSQL, API Platform)
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
   * **API Platform / Swagger UI**: `https://localhost`
   * **MinIO Web Console (Local S3)**: `http://localhost:9001` *(User: `minioadmin` / Pass: `minioadmin`)*

4. **Stop the environment**:
   ```bash
   docker compose down
   ```

---

## 🛠️ Technology Stack

* **Framework**: Symfony 7 + API Platform 3
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
