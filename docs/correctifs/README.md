# Documents correctifs

Rapport complet des interventions réalisées le 24 octobre 2026 sur la copie de travail du projet **Compliance Store** (dépôt `tontonlionelAPI`, dépôt personnel de Hiro, issu de `lionelkouame/compliance-store`).

Cette documentation est indépendante de la documentation existante (`docs/README.md`, ADR, workflows) : elle décrit uniquement ce qui a été changé, pourquoi, et comment le vérifier.

## Sommaire

| # | Document | Sujet |
|---|----------|-------|
| 1 | [Environnement de développement local](01-environnement-local.md) | Installation de la stack sur Windows, ports, `.env.local`, `make` |
| 2 | [Stack Docker : Mercure et FrankenPHP](02-stack-docker-mercure.md) | Conteneur PHP en boucle de redémarrage, Caddyfile et `compose.override.yaml` |
| 3 | [Doctrine : montée de DBAL en 4.5](03-doctrine-dbal.md) | `doctrine:schema:update` cassé, base de test vide, 29 tests en erreur |
| 4 | [Code : 9 erreurs PHPStan et test en échec](04-phpstan-holder.md) | Agrégat Holder, `DocumentProcessor`, `BirthDate`, tests API |
| 5 | [Dépôt Git et Dependabot](05-depot-git-et-dependabot.md) | Commits, branches, push vers le dépôt personnel, désactivation de Dependabot |
| 6 | [Améliorations](06-ameliorations.md) | Dépendances à jour, persistance complète de `Holder`, `Document.holderId`, `GET /v1/holders/{id}` |
| 7 | [Audit complet du projet](07-audit.md) | Revue de chaque dossier et fichier, 60 corrections (sécurité Compose, Caddyfile prod, types Doctrine, chiffrement, outillage, docs), points de conception restants |
| 8 | [Corrections prioritaires](08-corrections-prioritaires.md) | Clés d'API et rôles, limites de dépôt, effacement RGPD persistable avec suppression cryptographique, compensation du stockage, lecture et téléchargement des documents, journal d'audit |
| 9 | [Compliance Store expliqué à tout le monde](09-presentation-generale.md) | Intention de l'application sans jargon : problème résolu, fonctions, rôles, protections, limites actuelles |
| 10 | [Documentation technique](10-documentation-technique.md) | Architecture, modèle de données, cas d'usage, chiffrement, authentification, référence de l'API, configuration, tests, exploitation |
| — | [Journal de bord](JOURNAL.md) | Chronologie horodatée de toutes les actions |

## Résumé en une page

**Point de départ.** Le dépôt venait d'être cloné sur un poste Windows 11 sans PHP ni Composer, avec Docker Desktop installé mais arrêté. Les deux derniers commits (`feat: wip`) introduisaient l'agrégat `Holder` et n'étaient pas terminés.

**Constats.**

1. Le conteneur PHP redémarrait en boucle : l'image `dunglas/frankenphp:1-php8.5` embarque désormais un module Mercure plus strict que ce que la `Caddyfile.dev` du projet déclarait.
2. `doctrine:schema:update` échouait : `doctrine/orm` 3.6.9 et `symfony/doctrine-bridge` 8.1 exigent `doctrine/dbal` 4.5, le lock installait 4.4.4. La base de test restait vide et 29 tests API tombaient.
3. PHPStan (niveau max) signalait 9 erreurs dans le code `Holder` en cours, et `POST /api/v1/documents` répondait 500.
4. Un PostgreSQL Windows occupait déjà les ports 5432 et 5433.

**Corrections.**

| Zone | Fichiers | Résultat |
|------|----------|----------|
| Stack Docker | `frankenphp/Caddyfile.dev`, `compose.override.yaml` | Conteneur PHP sain, API en HTTP et HTTPS |
| Dépendances | `composer.lock` | `doctrine/dbal` 4.4.4 → 4.5.0, `schema:update` fonctionne |
| Code | 9 fichiers `src/`, 2 fichiers `tests/`, `CHANGELOG.md` | PHPStan 0 erreur, PHPUnit 88 tests OK |
| Dépôt personnel | `.github/dependabot.yaml` supprimé | Plus de PR automatiques |

**Améliorations (phase 2, document 6).** Dépendances montées (Symfony 8.1.9, Doctrine ORM 3.7.5, PHPStan 2.3.1, PHPUnit 13.4.3), agrégat `Holder` entièrement persisté avec un type Doctrine `birth_date`, `Document.holderId` persisté et contrôlé (un holder inconnu donne 422), nouvel endpoint `GET /v1/holders/{id}`, repository déplacé au bon endroit, migration `Version20261024071500`.

**Audit (phase 3, document 7).** Revue de l'intégralité du dépôt, fichier par fichier. Corrections les plus importantes : secrets de développement retirés du `compose.yaml` de base et rendus obligatoires en production, Caddyfile de production aligné sur Mercure ≥ 0.21 et débarrassé des directives TLS de développement, image FrankenPHP épinglée, types Doctrine qui lèvent une erreur au lieu de retourner `NULL`, déchiffrement robuste aux données tronquées, `GET /v1/health` qui rapporte le vrai environnement, Makefile et fichiers `.http` corrigés, liens morts de la documentation réparés.

**Corrections prioritaires (phase 4, document 8).** Les cinq faiblesses relevées après l'audit sont traitées dans l'ordre : authentification par clés d'API hachées avec trois rôles hiérarchisés (`ROLE_ADMIN` > `ROLE_OPERATOR` > `ROLE_READER`, ADR 0013), limites de taille et de type sur les dépôts jusqu'à Caddy (413), parcours RGPD d'effacement exposé et persisté avec suppression cryptographique des documents du porteur, compensation du stockage quand la persistance échoue, lecture des documents (métadonnées, liste par porteur, téléchargement déchiffré avec contrôle d'intégrité) et journal d'audit de toutes les actions sur des données personnelles.

**Vérification finale.** `make test` passe intégralement : Deptrac 0 violation, PHPStan 0 erreur, PHPUnit 159 tests et 381 assertions (81 tests et 1 échec avant intervention, 93 avant l'audit, 107 avant les corrections prioritaires). En dev, `doctrine:schema:validate` confirme que la base est en phase avec le mapping ; les deux Caddyfiles passent `frankenphp validate`.

**Commits.**

| Commit | Branche | Message |
|--------|---------|---------|
| `726d17b` | `main`, `fix/holder-phpstan-and-dev-stack` | `fix(dev-stack): run on current FrankenPHP image and DBAL 4.5` |
| `77465e6` | `main`, `fix/holder-phpstan-and-dev-stack` | `fix(holder): resolve PHPStan errors and failing document upload` |
| `cb43859` | `main` uniquement | `chore: disable Dependabot on this personal copy` |
| `e0cdb12` | `main` | `docs(correctifs): report of the 24 October 2026 interventions` |
| `6fcfe51` | `main` | `chore(deps): update Symfony 8.1.9, Doctrine ORM 3.7.5, PHPStan 2.3.1, PHPUnit 13.4.3` |
| `ac50bb0` | `main` | `feat(holder): persist the whole aggregate, attach documents, expose GET /v1/holders/{id}` |
| `fd1e1ff` | `main` | `chore(deps): raise version floors to the installed releases` |
| `f4ad5d3` | `main` | `docs(correctifs): improvements report and logbook` |
| `1f7a07e` | `main` | `fix: project-wide audit (compose secrets, prod Caddyfile, Doctrine types, cipher robustness, tooling, docs)` |
| `ca1e323` | `main` | `docs(correctifs): project-wide audit report and logbook` |
| `5c2a90b` | `main` | `feat(security): authenticate API clients with hashed bearer API keys and a role hierarchy` |
| `78bf646` | `main` | `feat(document): enforce upload size and content-type limits end to end` |
| `6487af0` | `main` | `feat(gdpr): persist the erasure workflow with crypto-shredding, refuse orphan ciphertexts` |
| `5237134` | `main` | `feat(document): read documents back and keep an audit trail of every personal-data action` |

## À faire par chaque membre de l'équipe après avoir récupéré ces changements

Ces étapes valent pour **tout le monde** (développeurs, intégrateurs, exploitation), pas seulement pour la personne qui a réalisé les corrections.

1. **Reconstruire l'image PHP** : `frankenphp/conf.d/10-app.ini` (limites de dépôt) est copié dans l'image, un simple redémarrage ne suffit pas.
   ```bash
   make build
   make up
   ```
2. **Appliquer les migrations** : trois nouvelles tables ou colonnes (`api_client`, `holder.birth_date` nullable, `audit_event`).
   ```bash
   make migrate
   ```
3. **Créer sa clé d'API** : toute requête sauf `GET /v1/health` et `/api/docs` est refusée (401) sans clé. La clé s'affiche une seule fois, elle n'est pas récupérable ensuite.
   ```bash
   make sf c="app:api-client:create <votre-nom-ou-service> --role=ROLE_ADMIN"
   ```
   Choisir le rôle le plus faible qui suffit : `ROLE_READER` (lecture), `ROLE_OPERATOR` (porteurs, documents, téléchargement), `ROLE_ADMIN` (espaces de stockage, effacement RGPD, journal d'audit). Une clé compromise se révoque immédiatement : `make sf c="app:api-client:revoke <nom>"`.
4. **Renseigner les requêtes `.http`** : copier `tests/http/http-client.private.env.json.dist` vers `tests/http/http-client.private.env.json` (ignoré par Git) et y coller la clé dans `apiKey`. Toutes les requêtes de `tests/http/` envoient `Authorization: Bearer {{apiKey}}`.
5. **Swagger UI** (`/api`) : cliquer sur « Authorize » et saisir la clé comme jeton Bearer.
6. **Vérifier** : `make test` doit afficher Deptrac 0 violation, PHPStan 0 erreur, PHPUnit 159 tests.

En production, les secrets restent obligatoires (`DOCUMENT_MASTER_KEY`, `AWS_S3_ACCESS_KEY`, `AWS_S3_SECRET_KEY`, `POSTGRES_PASSWORD`) et la première clé d'administrateur se crée avec la même commande console dans le conteneur ; voir `docs/docker-infra/production.md`.

## Ce qui reste ouvert

Les points de conception listés à la fin du document 4 ont été traités dans le document 6. Les cinq points de priorité haute de l'audit (document 7, section 7.8) ont été traités dans le document 8. Restent ouverts les points 6 à 27 de la section 7.8 et les points ajoutés en 8.8. Les principaux :

- Rotation et expiration des clés d'API, limitation de débit : une clé compromise se révoque à la main.
- Rétention du journal d'audit et sort de l'enregistrement anonymisé (lisible, ou 410) : décisions de conformité.
- `IdentityDocument` n'est rattaché à aucun porteur et échappe donc à l'anonymisation.
- `HolderAlreadyExistsException` teste un UUID fraîchement généré ; une vraie règle de dédoublonnage est une décision fonctionnelle.
- API Platform 5 et super-linter 9 sont des versions majeures à planifier séparément.
