# Vue d'ensemble de l'Architecture (Clean Architecture & DDD)

Compliance Store adopte les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design** (DDD).

---

## 🏗️ Structure des Couches Applicatives

```text
src/
├── Domain/                        # COUCHE DOMAINE (métier pur, zéro dépendance externe)
│   ├── Entity/                    # Agrégats : Document, Holder, IdentityDocument, StorageSpace, AuditEvent
│   ├── ValueObject/               # Objets de valeur immuables (DocumentId, HolderId, BirthDate, FileHash, EncryptedPayload…)
│   ├── Exception/                 # Exceptions métier (HolderNotFoundException, StorageSpaceCodeAlreadyExistsException…)
│   ├── Event/                     # Réservé : événements métier (vide à ce jour)
│   └── Port/                      # Contrats d'interfaces (ports hexagonaux)
│       ├── Gateway/               # CipherGatewayInterface, StorageGatewayInterface
│       ├── Repository/            # DocumentRepositoryInterface, HolderRepositoryInterface…
│       ├── Service/               # IdValidatorInterface, AuditTrailInterface, générateurs d'identifiants
│       ├── Clock/                 # Réservé : horloge injectable (vide à ce jour)
│       ├── Event/                 # Réservé : publication d'événements (vide à ce jour)
│       └── Notification/          # Réservé : notifications (vide à ce jour)
│
├── Application/                   # COUCHE APPLICATION (orchestration des cas d'usage)
│   ├── UseCase/                   # Un dossier par cas d'usage : StoreDocument, ConsultDocument, CreateHolder, AnonymizeHolder, ListAuditEvents…
│   │                              # chaque dossier porte son UseCase et sa Command (DTO d'entrée)
│   └── Dto/                       # Réservé (vide à ce jour)
│
└── Infrastructure/                # COUCHE INFRASTRUCTURE (implémentations techniques)
    ├── Gateway/                   # SodiumCipherGateway (chiffrement d'enveloppe Libsodium)
    ├── Storage/                   # MinioStorageGateway (Flysystem S3), StorageKey
    ├── Persistence/Doctrine/      # Repositories Doctrine, Mapping/ (XML), Type/ (types DBAL des Value Objects)
    ├── Service/                   # Générateurs d'UUID et validateur d'identifiants (Symfony Uid)
    ├── Security/                  # ApiClient (clé d'API hachée), ApiKeyTokenHandler, ApiKeyEntryPoint, Mapping/ (ADR 0013)
    ├── Audit/                     # SecurityAwareAuditTrail (acteur = client d'API authentifié)
    ├── Console/                   # app:api-client:create, app:api-client:revoke
    └── Presentation/ApiPlatform/  # V1/Resource/ (ressources API Platform) et V1/State/ (providers et processors)
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

* Chaque propriété scalaire d'une Entité est portée par un Value Object dédié (`DocumentId`, `HolderId`...) qui valide son invariant dans son constructeur.
* Une liste est modélisée par un Value Object **collection**, immuable, implémentant `IteratorAggregate` + `Countable` (ex: `AllowedDocumentTypes`) — jamais par une méthode `toArray()` exposée au Domaine.
* La conversion vers un tableau brut reste un détail d'Infrastructure (Doctrine Types personnalisés, mapping vers les DTOs `#[ApiResource]`), jamais une responsabilité du Domaine.
* Seuls les DTOs applicatifs (`Application/UseCase/*/*Command`), qui traversent la (dé)sérialisation HTTP, restent primitifs — c'est au Use Case de construire les Value Objects avant d'appeler le Domaine.
