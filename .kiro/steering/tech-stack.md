---
inclusion: always
---

# Stack technique

| Brique | Version | Note |
| :--- | :--- | :--- |
| PHP | `>= 8.4` | plateforme figée à `8.4.1` dans `composer.json` |
| Symfony | `8.1.*` | contrainte stricte — ne pas élargir sans ADR |
| API Platform | `^4.3` | exposition REST, State Providers/Processors |
| Doctrine ORM | `^3.6.7, <3.6.8` | **borne haute volontaire**, ne pas lever |
| PostgreSQL | 16 | |
| Stockage objet | MinIO via `league/flysystem-aws-s3-v3` | |
| Chiffrement | `ext-sodium` | chiffrement enveloppe, ADR 0002 |
| Serveur | FrankenPHP + Caddy | Docker Compose |

Autoload : `App\` → `src/`, `App\Tests\` → `tests/` (PSR-4).

---

## Commandes — toujours passer par le Makefile

```bash
make start      # build + up
make test       # deptrac → phpstan → phpunit   ← LE gate
make deptrac    # respect des couches Clean Architecture
make phpstan    # analyse statique, niveau max
make sf c=...   # console Symfony dans le conteneur
make sh         # shell dans le conteneur php
```

**`make test` enchaîne les trois vérifications.** C'est la seule commande qui autorise à
dire qu'une tâche est terminée. Ne jamais lancer `phpunit` seul et en conclure que c'est vert.

Plusieurs worktrees en parallèle : tous les ports publiés sont paramétrables via
`.env.local` (non versionné, chargé par le Makefile) — voir
`docs/docker-infra/parallel-worktrees.md`.

Les commandes s'exécutent **dans le conteneur** (`docker compose exec php`). Ne jamais
invoquer `php`, `composer` ou `vendor/bin/*` directement depuis l'hôte.

---

## Couches et règle de dépendance

Contrôlée mécaniquement par `deptrac.yaml` — ce n'est pas une convention, c'est un test :

```bash
Domain          → rien   (PHP pur : ni Symfony, ni Doctrine, ni API Platform)
Application     → Domain
Infrastructure  → Domain, Application, Symfony, Doctrine, ApiPlatform
```

```text
src/
├── Domain/          Entity · ValueObject · Event · Exception
│   └── Port/        Gateway · Repository · Service · Clock · Event · Notification
├── Application/     UseCase/<NomDuCasDUsage>/ · Dto/
└── Infrastructure/  Gateway · Storage · Persistence/Doctrine · Service
    └── Presentation/ApiPlatform/V1/{Resource,State}
```

Un cas d'usage = **un dossier** `Application/UseCase/<Nom>/` contenant sa commande et son handler.

---

## Règles de conception non négociables

- **Pas de primitif dans la surface publique du Domaine** (ADR 0004). Chaque scalaire métier
  est porté par un Value Object immuable qui valide son invariant dans son constructeur.
  Une liste est un Value Object collection (`IteratorAggregate` + `Countable`), pas un `array`.
- Seuls les **DTO applicatifs** (`Application/UseCase/*/*Command`), qui traversent la
  (dé)sérialisation HTTP, restent primitifs. C'est au cas d'usage de construire les
  Value Objects avant d'appeler le Domaine.
- **API Platform est découplé du Domaine** (ADR 0003) : les `#[ApiResource]` sont des DTO
  d'infrastructure, jamais des entités. Le passage se fait par State Provider / Processor.
- **Double verrou de validation** (ADR 0005) : contraintes déclaratives sur le DTO **et**
  invariant dans le Value Object. Les deux, pas l'un ou l'autre.
- Le mapping Doctrine est en **XML** (`Infrastructure/Persistence/Doctrine/Mapping/`), pas en
  attributs — le Domaine ne doit pas connaître Doctrine.

---

## Avant d'ajouter une dépendance

Une dépendance dans `Domain/` est presque toujours une erreur de conception : `deptrac`
la refusera. Une nouvelle dépendance Composer se justifie dans la PR, et touche au
périmètre d'un ADR si elle entre dans une décision structurante.
