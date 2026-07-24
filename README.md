# Compliance Store API

**Compliance Store** est une API REST open-source hautement disponible et sécurisée pour la gestion du cycle de vie des documents sensibles (CNI, passeports, justificatifs de domicile).

Elle offre une gestion fine de la conformité réglementaire, du chiffrement enveloppe *Zero Trust*, du masquage/redaction automatique des données PII, et s'interface avec n'importe quel fournisseur de stockage (S3, MinIO, Azure Blob, Disque local).

---

## 🏛️ Architecture & Conception

L'application est développée en **PHP 8.3+** avec **Symfony 7** & **API Platform**, en suivant strictement les principes de la **Clean Architecture / DDD (Domain-Driven Design)** :

```text
src/
├── Domain/              # Logique métier pure (Entités, Value Objects, Socle réglementaire)
│   └── Port/            # Interfaces (Gateways, Repositories, Events)
├── Application/         # Cas d'usage (Use Cases & DTOs)
└── Infrastructure/      # Implémentations techniques (MinIO, Sodium, PostgreSQL, API Platform)
```

Pour consulter la documentation détaillée de l'architecture :
* 📘 [Table des Matières de la Documentation](docs/README.md)
* 📜 [Décisions d'Architecture (ADRs)](docs/architecture/adr/)
* 🔄 [Workflows d'Ingestion et de Consultation](docs/architecture/workflows/)

---

## 🚀 Démarrage Rapide (Environnement de Développement)

L'application tourne sur **FrankenPHP** et **Caddy** via Docker Compose.

### Prérequis
* Docker et Docker Compose (v2.10+)

### Lancement

1. **Construire les images Docker** :
   ```bash
   docker compose build --pull --no-cache
   ```

2. **Démarrer les services** (API FrankenPHP + PostgreSQL + MinIO) :
   ```bash
   docker compose up -d
   ```

3. **Accéder à l'application** :
   * **API Platform / Swagger UI** : `https://localhost`
   * **Console Web MinIO (S3 Local)** : `http://localhost:9001` *(User: `minioadmin` / Pass: `minioadmin`)*

4. **Arrêter l'environnement** :
   ```bash
   docker compose down
   ```

---

## 🛠️ Stack Technique

* **Framework** : Symfony 7 + API Platform 3
* **Serveur HTTP / Runtime** : FrankenPHP + Caddy (Worker Mode)
* **Base de données** : PostgreSQL 16
* **Stockage Objets** : MinIO (compatible S3) / League Flysystem
* **Chiffrement** : Libsodium (`sodium_crypto_secretbox`)

---

## 📖 Template de base

Ce projet est basé sur le template [dunglas/symfony-docker](https://github.com/dunglas/symfony-docker).
Pour consulter la documentation d'origine de l'infrastructure Docker/FrankenPHP, voir [README.symfony-docker.md](README.symfony-docker.md) ou le dossier [docs/docker-infra/](docs/docker-infra/).

---

## 📄 Licence

Ce projet est sous licence MIT.
