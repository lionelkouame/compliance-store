# Vue d'ensemble de l'Architecture (Clean Architecture & DDD)

Compliance Store adopte les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design** (DDD).

---

## 🏗️ Structure des Couches Applicatives

Le code est organisé **par couche**, puis **par fonctionnalité** (`Holder`, `NaturalPerson`, `StorageSpace`, `IdentityDocument`, `Document`, `System`).

```text
src/
├── Domain/                     # COUCHE DOMAINE (Métier pur, zéro dépendance externe)
│   ├── Entity/                 # Entités & Agrégats — n'exposent que des Value Objects
│   ├── ValueObject/<Feature>/  # Objets de valeur immuables, invariants validés à la construction
│   ├── ReadModel/<Feature>/    # Projections en lecture seule renvoyées par les use cases
│   ├── Exception/              # Catégories d'exceptions métier + exceptions par fonctionnalité (ADR 0013)
│   └── Port/                   # Contrats d'interfaces (Ports Hexagonaux)
│       ├── Gateway/            # StorageGatewayInterface, CipherGatewayInterface
│       ├── Repository/         # HolderRepositoryInterface, StorageSpaceRepositoryInterface...
│       └── Service/            # GeneratorInterface, IdValidatorInterface...
│
├── Application/                # COUCHE APPLICATION (Orchestration des cas d'usage)
│   └── UseCase/<Feature>/<Action>/
│       ├── <Action>Request.php   # Données d'entrée primitives
│       ├── <Action>Response.php  # Enveloppe un ReadModel du Domaine
│       └── <Action>UseCase.php   # Orchestration
│
└── Infrastructure/             # COUCHE INFRASTRUCTURE (Implémentations techniques)
    ├── Gateway/                # SodiumCipherGateway (chiffrement d'enveloppe)
    ├── Storage/                # MinioStorageGateway (stockage S3-compatible)
    ├── Persistence/Doctrine/   # Repositories, mappings XML et types Doctrine (PostgreSQL)
    ├── Service/                # Générateurs et validateurs d'UUID
    └── Presentation/ApiPlatform/V1/
        ├── Resource/<Feature>/ # <Feature>Resource (#[ApiResource]) + DTOs <Action>Input / <Action>Output
        └── State/
            ├── Processor/<Feature>/  # Écritures (POST, PATCH)
            └── Provider/<Feature>/   # Lectures (GET)
```

---

## 🎯 Règle de Dépendance

Les dépendances pointent toujours **vers l'intérieur** :
* `Infrastructure` dépend de `Application` et `Domain`.
* `Application` dépend de `Domain`.
* `Domain` ne dépend de **personne** (PHP pur sans Symfony ni Doctrine).

La règle est vérifiée automatiquement par **Deptrac** (`make deptrac`, `deptrac.yaml`).

---

## 🔁 Cycle d'une requête

Chaque opération HTTP traverse les couches de la même façon. Les **Processors / Providers** ne sont que des adaptateurs : ils convertissent, ils ne décident rien ([ADR 0003](../adr/0003-api-platform-decoupling-state-processors.md)).

```text
 HTTP (JSON)
    │
    ▼
 <Action>Input            DTO HTTP, contraintes #[Assert] déclaratives (ADR 0005)
    │  désérialisé par API Platform (deserialize: true)
    ▼
 Processor / Provider     valide l'Input, construit la Request
    │
    ▼
 <Action>Request          primitives uniquement (string, ?string...)
    │
    ▼
 <Action>UseCase          construit les Value Objects, applique les règles, appelle les Ports
    │
    ▼
 <Action>Response         enveloppe un ReadModel du Domaine (jamais une Entité)
    │
    ▼
 Processor / Provider     convertit le ReadModel en Output
    │
    ▼
 <Action>Output           DTO HTTP, primitives uniquement → JSON / JSON-LD
```

### Conventions des Use Cases

* Un use case implémente `UseCaseInterface<Request, Response>` ; sa Request implémente `UseCaseRequestInterface` et sa Response `UseCaseResponseInterface`.
* Exemple de référence : `Application/UseCase/Holder/CreateHolder/`.

```php
/**
 * @implements UseCaseInterface<CreateHolderRequest, CreateHolderResponse>
 */
final readonly class CreateHolderUseCase implements UseCaseInterface
{
    public function execute(UseCaseRequestInterface $request): CreateHolderResponse
    {
        // Request (primitives) → Value Objects → Entité → Port
        // ...
        return new CreateHolderResponse(new CreateHolderRead($holderId));
    }
}
```

* La Response porte un **ReadModel** (`Domain/ReadModel/<Feature>/`) : l'Entité ne sort jamais de la couche Application.
* Un use case ne renvoie jamais `null` : l'absence de résultat est une exception métier (`XxxNotFoundException`).
* Une opération sans paramètre reçoit quand même une Request vide (`ListStorageSpacesRequest`, `CheckSystemStatusRequest`), pour pouvoir ajouter des critères sans changer la signature.

### Conventions de la couche Présentation

* Une Resource par fonctionnalité, qui ne déclare que les opérations (`shortName`, `uriTemplate`, `input`, `output`, `processor` / `provider`).
* Un DTO d'Input par opération d'écriture. Les opérations GET n'ont **pas** d'Input : l'identifiant vient de `$uriVariables` ([ADR 0007](../adr/0007-declarative-uri-variables-contract-in-state-providers.md)).
* Un DTO d'Output par opération, ou un Output partagé lorsque plusieurs opérations exposent exactement la même représentation (`StorageSpaceOutput`).

---

## 🚨 Gestion des erreurs

Les erreurs métier sont **classées par catégorie** dans le Domaine et **traduites en HTTP une seule fois**, par configuration ([ADR 0013](../adr/0013-business-exceptions-translated-once-at-the-boundary.md)). Les Processors et Providers ne contiennent **aucun `try/catch`**.

| Catégorie (`Domain/Exception/`) | Exemple                                                                                   | Statut HTTP |
| ------------------------------- | ----------------------------------------------------------------------------------------- | ----------- |
| `NotFoundException`             | `HolderNotFoundException`                                                                 | 404         |
| `AlreadyExistException`         | `StorageSpaceCodeAlreadyExistsException`                                                  | 409         |
| `InvalidDataException`          | `InvalidValueException` (levée par les Value Objects), `IdentityDocumentExpiredException` | 422         |

* Une nouvelle exception métier hérite simplement de la bonne catégorie : aucune modification côté HTTP.
* `\InvalidArgumentException` est interdite dans `src/Domain` et `src/Application` (test d'architecture `tests/Unit/Architecture/`).
* Les messages d'exception partent au client : ils ne contiennent **jamais** de données personnelles.

---

## 🧱 Value Objects (pas de types primitifs, pas de tableaux natifs)

Le Domaine n'expose jamais de `string`, `bool` métier non typé ou `array` brut dans sa surface publique — voir [ADR 0004](../adr/0004-value-objects-in-domain.md).

* Chaque propriété scalaire d'une Entité est portée par un Value Object dédié (`DocumentId`, `HolderId`...) qui valide son invariant dans son constructeur et lève une `InvalidValueException` en cas de violation.
* Une liste est modélisée par un Value Object **collection**, immuable, implémentant `IteratorAggregate` + `Countable` (par exemple un futur `AllowedDocumentTypes` ; aucune collection n'existe encore) — jamais par une méthode `toArray()` exposée au Domaine.
* La conversion vers un tableau brut reste un détail d'Infrastructure (Doctrine Types personnalisés, mapping vers les DTOs d'Output), jamais une responsabilité du Domaine.
* Seules les **Requests** des use cases (`Application/UseCase/*/*/*Request`) et les DTOs d'Input / Output restent primitifs — c'est au Use Case de construire les Value Objects avant d'appeler le Domaine.

---

## ✅ Qualité

| Outil   | Commande       | Vérifie                                                               |
| ------- | -------------- | --------------------------------------------------------------------- |
| PHPStan | `make phpstan` | Analyse statique, niveau max (y compris les génériques des use cases) |
| Deptrac | `make deptrac` | Règle de dépendance entre couches                                     |
| PHPUnit | `make test`    | Tests unitaires (Domain, Application, architecture) et tests API      |

---

## 📜 Décisions d'architecture

Les décisions détaillées sont consignées dans les [ADR](../adr/) ; voir l'index dans [`docs/README.md`](../../README.md).
