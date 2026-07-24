# Documentation de Compliance Store

Bienvenue dans la documentation officielle du projet **Compliance Store**.

---

## 📘 Documentation Métier & Architecture

* **[Aperçu Global & Architecture](architecture/overview/index.md)** : Clean Architecture, principes DDD, structure du code et couches applicatives.
* **[Registre des Décisions d'Architecture (ADRs)](architecture/adr/)** :
  * [ADR 0001 : Socle réglementaire natif et Port d'extension Provider](architecture/adr/0001-native-compliance-and-extension-port.md)
  * [ADR 0002 : Chiffrement Enveloppe et Zero Trust Storage](architecture/adr/0002-envelope-encryption-zero-trust.md)
  * [ADR 0003 : Découplage d'API Platform via State Processors / Providers](architecture/adr/0003-api-platform-decoupling-state-processors.md)
* **Workflows & Pipelines** :
  * [Pipeline d'Ingestion & Redaction PII](architecture/workflows/ingestion.md)
  * [Pipeline de Consultation & Déchiffrement](architecture/workflows/consultation.md)
* **[Spécifications de l'API REST](architecture/api/endpoints.md)** : Entrées/Sorties, DTOs et codes HTTP.

---

## 🛠️ Documentation Infrastructure & DevOps (FrankenPHP / Docker)

Toute la documentation technique liée à l'environnement Docker Compose, FrankenPHP et Caddy est disponible dans le dossier **[docker-infra/](docker-infra/)** :

* [Options du Template Symfony Docker](docker-infra/options.md)
* [Guide de Déploiement en Production](docker-infra/production.md)
* [Configuration du Débogage avec Xdebug](docker-infra/xdebug.md)
* [Gestion des Certificats TLS / HTTPS](docker-infra/tls.md)
* [Ajout de Services Docker Supplémentaires](docker-infra/extra-services.md)
* [Guide de Dépannage (Troubleshooting)](docker-infra/troubleshooting.md)
