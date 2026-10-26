# Documentation de Compliance Store

Bienvenue dans la documentation officielle du projet **Compliance Store**.

---

## 🧭 Par où commencer

* **[Compliance Store expliqué à tout le monde](correctifs/09-presentation-generale.md)** : ce que fait l'application, pourquoi, et ce qu'elle ne fait pas encore, sans jargon. Pour la direction, le métier, le juridique, le support et les nouveaux arrivants.
* **[Documentation technique](correctifs/10-documentation-technique.md)** : architecture, modèle de données, cas d'usage, chiffrement, authentification, référence de l'API, configuration, tests, exploitation, points ouverts. Pour les développeurs, intégrateurs et exploitants.

---

## 📘 Documentation Métier & Architecture

* **[Aperçu Global & Architecture](architecture/overview/index.md)** : Clean Architecture, principes DDD, structure du code et couches applicatives.
* **[Registre des Décisions d'Architecture (ADRs)](architecture/adr/)** :
  * [ADR 0001: Native Compliance Core and Provider Extension Port](architecture/adr/0001-native-compliance-and-extension-port.md)
  * [ADR 0002: Envelope Encryption and Zero Trust Storage](architecture/adr/0002-envelope-encryption-zero-trust.md)
  * [ADR 0003: Decoupling API Platform via State Processors / Providers](architecture/adr/0003-api-platform-decoupling-state-processors.md)
  * [ADR 0004: Mandatory Value Objects in the Domain (no primitives, no native arrays)](architecture/adr/0004-value-objects-in-domain.md)
  * [ADR 0005: Declarative DTO Validation with Infrastructure Delegation and Domain Double-Lock Guard](architecture/adr/0005-declarative-dto-validation-and-domain-double-lock.md)
  * [ADR 0006: Strict Non-Nullable Value Objects and Domain-Level Optionality Handling](architecture/adr/0006-handling-optional-domain-attributes-and-nullability.md)
  * [ADR 0007: Declarative URI Variables Contract in API Platform State Providers via PHPDoc Shapes](architecture/adr/0007-declarative-uri-variables-contract-in-state-providers.md)
  * [ADR 0008: Infrastructure Refactoring](architecture/adr/0008-infra-refactorisation.md)
  * [ADR 0009: Compliance Relevance of Document's Value Objects](architecture/adr/0009-document-value-objects-compliance-relevance.md)
  * [ADR 0010: Binary Responses Escape the State Provider Contract](architecture/adr/0010-binary-responses-escape-state-provider-contract.md)
  * [ADR 0011: Binary Responses Are Served by State Providers Returning a Response](architecture/adr/0011-binary-responses-served-by-state-providers.md)
  * [ADR 0012: Regulation Engine Extracted as an External, Reusable Provider](architecture/adr/0012-external-regulation-engine-as-provider.md)
  * [ADR 0013: API Clients Authenticate with Hashed Bearer API Keys](architecture/adr/0013-api-key-authentication.md)
* **Spécifications de l'API REST** : la documentation OpenAPI est générée par API Platform et servie sur `/api` (Swagger UI) et `/api/docs.json`. Les requêtes d'exemple sont dans `tests/http/`.
* **[Documents correctifs](correctifs/README.md)** : interventions du 24 octobre 2026 (stack Docker, Doctrine, PHPStan, améliorations, audit, corrections prioritaires), journal de bord, et la **liste des étapes à suivre par chaque membre de l'équipe** après récupération des changements (reconstruction de l'image, migrations, création de sa clé d'API).

---

## 🛠️ Documentation Infrastructure & DevOps (FrankenPHP / Docker)

Toute la documentation technique liée à l'environnement Docker Compose, FrankenPHP et Caddy est disponible dans le dossier **[docker-infra/](docker-infra/)** :

* [Options du Template Symfony Docker](docker-infra/options.md)
* [Configuration du DNS Local (compliance-store.loc)](docker-infra/dns.md)
* [Guide de Déploiement en Production](docker-infra/production.md)
* [Configuration du Débogage avec Xdebug](docker-infra/xdebug.md)
* [Gestion des Certificats TLS / HTTPS](docker-infra/tls.md)
* [Ajout de Services Docker Supplémentaires](docker-infra/extra-services.md)
* [Guide de Dépannage (Troubleshooting)](docker-infra/troubleshooting.md)
