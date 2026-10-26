---
sidebar_position: 3
---

# Architecture

Compliance Store suit les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design**. L'objectif : garder le cœur métier indépendant de tout framework ou détail technique, pour qu'il reste testable et facile à faire évoluer.

## La pile technique

PHP 8.5 sur FrankenPHP et Caddy, Symfony 8.1, API Platform 4.3, Doctrine ORM avec PostgreSQL 16, Flysystem vers MinIO ou tout stockage compatible S3, Libsodium pour le chiffrement. Le tout tourne avec Docker Compose.

## Les trois couches

```text
src/
├── Domain/            # Métier pur : aucun import Symfony, Doctrine ou API Platform
│   ├── Entity/          # Holder, Document, IdentityDocument, StorageSpace, AuditEvent
│   ├── ValueObject/     # HolderId, BirthDate, FileHash, WrappedDataKey, AuditAction…
│   ├── Exception/       # HolderNotFoundException, DocumentIntegrityException…
│   └── Port/            # Interfaces attendues par le domaine
│       ├── Gateway/       # Chiffrement, stockage
│       ├── Repository/    # Persistance des entités
│       └── Service/       # Journal d'audit, génération d'identifiants
│
├── Application/
│   └── UseCase/         # 15 cas d'usage : StoreDocument, ConsultDocument, AnonymizeHolder…
│
└── Infrastructure/    # Implémentations techniques des ports
    ├── Persistence/     # Repositories Doctrine, mappings XML, types DBAL (PostgreSQL)
    ├── Presentation/    # Ressources API Platform, state providers et processors
    ├── Storage/         # Stockage S3 via Flysystem
    ├── Gateway/         # Chiffrement d'enveloppe Libsodium
    ├── Security/        # Clients d'API et authentification par clé
    ├── Audit/           # Journal d'audit (acteur = client authentifié)
    ├── Console/         # Commandes de création et de révocation des clés
    └── Service/         # Génération d'UUID, validation
```

## Règle de dépendance

Les dépendances pointent toujours **vers l'intérieur** :

- `Infrastructure` dépend de `Application` et de `Domain`.
- `Application` dépend de `Domain`.
- `Domain` ne dépend de personne (PHP pur, sans Symfony ni Doctrine).

Deptrac vérifie cette règle à chaque commit, aux côtés de PHPStan au niveau maximal et de la suite PHPUnit.

## Comment une requête traverse le code

1. Caddy reçoit la requête HTTPS et refuse d'emblée les corps de plus de 16 Mio.
2. Symfony Security vérifie la clé d'API et le rôle.
3. API Platform route vers une ressource ; son provider (lecture) ou processor (écriture) convertit la requête en commande.
4. Le cas d'usage applique les règles métier à travers les entités et value objects.
5. Les ports sont servis par Doctrine (PostgreSQL), le gateway Libsodium et le gateway Flysystem (S3).

## Les décisions d'architecture

Treize ADR tracent les choix structurants, consultables dans [`docs/architecture/adr/`](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr) :

| ADR | Décision |
|-----|----------|
| 0001 | Cœur de conformité natif et port d'extension pour des fournisseurs |
| 0002 | Chiffrement d'enveloppe et stockage Zero Trust |
| 0003 | Découplage d'API Platform par state processors et providers |
| 0004 | Value objects obligatoires dans le domaine |
| 0005 | Validation déclarative des DTO et double verrou du domaine |
| 0006 | Value objects non nullables, optionnalité gérée par le domaine |
| 0007 | Contrat déclaratif des variables d'URI dans les providers |
| 0008 | Refactorisation de l'infrastructure |
| 0009 | Pertinence réglementaire des value objects de Document |
| 0010 et 0011 | Réponses binaires servies par des providers renvoyant une Response |
| 0012 | Moteur de réglementation extrait comme fournisseur externe |
| 0013 | Authentification des clients d'API par clés Bearer hachées |

C'est la source de vérité technique ; cette page en donne une vue simplifiée. La [documentation technique complète](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/10-documentation-technique.md) détaille le modèle de données, chaque cas d'usage, le chiffrement, l'authentification et la référence de l'API.
