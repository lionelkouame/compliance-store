# Vue d'ensemble de l'Architecture (Clean Architecture & DDD)

Compliance Store adopte les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design** (DDD).

---

## 🏗️ Structure des Couches Applicatives

```text
src/
├── Domain/                   # COUCHE DOMAINE (Métier pur, zéro dépendance externe)
│   ├── Entity/               # Entités & Agrégats (Document, Policy, Rule)
│   ├── ValueObject/          # Objets de valeur immuables (DocumentId, DocumentType, Hash)
│   ├── Event/                # Événements métier (DocumentStoredEvent, DocumentPurgedEvent)
│   ├── Exception/            # Exceptions métier (NonCompliantDocumentException)
│   └── Port/                 # Contrats d'interfaces (Ports Hexagonaux)
│       ├── Gateway/          # StorageGatewayInterface, CipherGatewayInterface...
│       ├── Repository/       # DocumentRepositoryInterface...
│       ├── Event/            # EventPublisherInterface...
│       └── Clock/            # ClockInterface (PSR-20)
│
├── Application/              # COUCHE APPLICATION (Orchestration des cas d'usage)
│   ├── UseCase/              # StoreDocumentUseCase, ConsultDocumentUseCase
│   └── Dto/                  # UploadDocumentInput, DocumentOutput
│
└── Infrastructure/           # COUCHE INFRASTRUCTURE (Implémentations techniques)
    ├── Gateway/              # MinioStorageGateway, SodiumCipherGateway
    ├── Persistence/          # Repositories Doctrine (PostgreSQL)
    └── ApiPlatform/          # State Processors / Providers API Platform
```

---

## 🎯 Règle de Dépendance

Les dépendances pointent toujours **vers l'intérieur** :
* `Infrastructure` dépend de `Application` et `Domain`.
* `Application` dépend de `Domain`.
* `Domain` ne dépend de **personne** (PHP pur sans Symfony ni Doctrine).
