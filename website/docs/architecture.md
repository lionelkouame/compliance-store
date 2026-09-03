---
sidebar_position: 2
---

# Architecture

Compliance Store suit les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design**. L'objectif : garder le cœur métier indépendant de tout framework ou détail technique, pour qu'il reste testable et facile à faire évoluer.

## Les trois couches

```text
src/
├── Domain/           # Métier pur, zéro dépendance externe
│   ├── Entity/        # Entités & agrégats (Document, Policy, Rule)
│   ├── ValueObject/    # Objets de valeur immuables
│   ├── Service/        # Services de domaine sans état
│   ├── Event/          # Événements métier
│   ├── Exception/      # Exceptions métier
│   └── Port/            # Interfaces (ports hexagonaux)
│
├── Application/       # Orchestration des cas d'usage
│   ├── UseCase/         # StoreDocumentUseCase, ConsultDocumentUseCase...
│   └── Dto/              # Objets d'entrée/sortie
│
└── Infrastructure/    # Implémentations techniques
    ├── Gateway/          # Stockage (MinIO), chiffrement (Sodium)...
    ├── Persistence/       # Repositories Doctrine (PostgreSQL)
    └── ApiPlatform/        # State Processors / Providers API Platform
```

## Règle de dépendance

Les dépendances pointent toujours **vers l'intérieur** :

- `Infrastructure` dépend de `Application` et de `Domain`.
- `Application` dépend de `Domain`.
- `Domain` ne dépend de personne (PHP pur, sans Symfony ni Doctrine).

## Documentation complète

Les décisions d'architecture détaillées sont consignées sous forme d'**ADR (Architecture Decision Records)** dans le dépôt de code, à [`docs/architecture/adr/`](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr). C'est la source de vérité technique ; les pages de ce site en donnent une vue simplifiée et à jour dans les grandes lignes.
