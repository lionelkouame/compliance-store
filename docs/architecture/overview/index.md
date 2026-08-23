# Vue d'ensemble de l'Architecture (Clean Architecture & DDD)

Compliance Store adopte les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design** (DDD).

---

## 🏗️ Structure des Couches Applicatives

```text
src/
├── Domain/                   # COUCHE DOMAINE (Métier pur, zéro dépendance externe)
│   ├── Entity/                # Entités & Agrégats (Document, Policy, Rule) — n'exposent que des Value Objects
│   ├── ValueObject/           # Objets de valeur immuables (DocumentId, DocumentType, Hash) et leurs collections
│   ├── Service/                # Services de domaine sans état
│   ├── Event/                # Événements métier (DocumentStoredEvent, DocumentPurgedEvent)
│   ├── Exception/            # Exceptions métier (InvalidJurisdictionException...)
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

---

## 🧱 Value Objects (pas de types primitifs, pas de tableaux natifs)

Le Domaine n'expose jamais de `string`, `bool` métier non typé ou `array` brut dans sa surface publique — voir [ADR 0004](../adr/0004-value-objects-in-domain.md).

* Chaque propriété scalaire d'une Entité est portée par un Value Object dédié (`JurisdictionCode`, `LegalFrameworkName`...) qui valide son invariant dans son constructeur.
* Une liste est modélisée par un Value Object **collection**, immuable, implémentant `IteratorAggregate` + `Countable` (ex: `AllowedDocumentTypes`) — jamais par une méthode `toArray()` exposée au Domaine.
* La conversion vers un tableau brut reste un détail d'Infrastructure (Doctrine Types personnalisés, mapping vers les DTOs `#[ApiResource]`), jamais une responsabilité du Domaine.
* Seuls les DTOs applicatifs (`Application/UseCase/*/*Command`), qui traversent la (dé)sérialisation HTTP, restent primitifs — c'est au Use Case de construire les Value Objects avant d'appeler le Domaine.
