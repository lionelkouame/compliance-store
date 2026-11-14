---
sidebar_position: 2
---

# Architecture

Compliance Store suit les principes de la **Clean Architecture** (Robert C. Martin) et du **Domain-Driven Design**. L'objectif : garder le cœur métier indépendant de tout framework ou détail technique, pour qu'il reste testable et facile à faire évoluer.

## Les trois couches

```text
src/
├── Domain/            # Métier pur, zéro dépendance externe
│   ├── Entity/          # Entités & agrégats (Holder, Document, StorageSpace...)
│   ├── ValueObject/     # Objets de valeur immuables
│   ├── ReadModel/       # Projections en lecture seule renvoyées par les cas d'usage
│   ├── Exception/       # Exceptions métier, classées par catégorie
│   └── Port/            # Interfaces (ports hexagonaux)
│
├── Application/       # Orchestration des cas d'usage
│   └── UseCase/         # Un dossier par action : Request, Response, UseCase
│
└── Infrastructure/    # Implémentations techniques
    ├── Gateway/         # Chiffrement (Sodium)
    ├── Storage/         # Stockage (MinIO)
    ├── Persistence/     # Repositories Doctrine (PostgreSQL)
    └── Presentation/    # API Platform : ressources, DTOs, processors / providers
```

## Règle de dépendance

Les dépendances pointent toujours **vers l'intérieur** :

- `Infrastructure` dépend de `Application` et de `Domain`.
- `Application` dépend de `Domain`.
- `Domain` ne dépend de personne (PHP pur, sans Symfony ni Doctrine).

## Le parcours d'une requête

Chaque appel à l'API suit le même chemin, de l'extérieur vers le cœur métier, puis retour :

```text
JSON ──► Input (DTO) ──► Request ──► Use case ──► Response (ReadModel) ──► Output (DTO) ──► JSON
```

- Les **DTOs** d'entrée et de sortie appartiennent à la couche Présentation : ils ne contiennent que des types simples.
- Le **cas d'usage** construit les objets de valeur, applique les règles métier, et ne renvoie jamais une entité : seulement une projection en lecture seule (*read model*).
- Les **processors / providers** d'API Platform se contentent de convertir d'un format à l'autre.

## Les erreurs

Le métier ne connaît pas HTTP. Il signale un fait (« ce titulaire existe déjà », « cette valeur est invalide ») en levant une exception d'une **catégorie** donnée. La traduction en code HTTP est faite **une seule fois**, par configuration :

| Catégorie               | Signification                         | Code HTTP |
| ----------------------- | ------------------------------------- | --------- |
| `NotFoundException`     | L'élément demandé n'existe pas        | 404       |
| `AlreadyExistException` | L'élément existe déjà                 | 409       |
| `InvalidDataException`  | La donnée ou l'opération est invalide | 422       |

Les réponses d'erreur suivent le format standard `application/problem+json` (RFC 7807), et leurs messages ne contiennent jamais de données personnelles.

## Documentation complète

Les décisions d'architecture détaillées sont consignées sous forme d'**ADR (Architecture Decision Records)** dans le dépôt de code, à [`docs/architecture/adr/`](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr). C'est la source de vérité technique ; les pages de ce site en donnent une vue simplifiée et à jour dans les grandes lignes.
