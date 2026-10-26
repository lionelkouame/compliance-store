# 7. Audit complet du projet

Audit mené le 24 octobre 2026 après la phase d'amélioration (document 6). Consigne : relire chaque dossier et chaque fichier du dépôt, corriger les erreurs trouvées, consigner le reste. Tout ce qui est décrit ici est validé par `make test` (Deptrac, PHPStan niveau max, PHPUnit) et, pour la stack Docker, par `docker compose config` et `frankenphp validate`.

## 7.1 Périmètre et méthode

| Zone | Fichiers relus | Outils |
|------|----------------|--------|
| `src/Domain/` | 41 fichiers PHP, 6 `.gitkeep` | Lecture complète, Deptrac |
| `src/Application/`, `src/Infrastructure/`, `config/`, `migrations/` | 9 use cases, 5 ressources, 9 providers/processors, 12 types Doctrine, 4 repositories, 4 mappings XML, 9 migrations, 15 fichiers de config | Lecture complète, `lint:container`, `lint:yaml`, `doctrine:schema:validate` |
| `tests/` | 4 tests API, 12 tests unitaires, 9 fichiers `.http`, `phpunit.dist.xml`, `deptrac.yaml`, `phpstan.neon` | Lecture complète, exécution |
| Docker, CI, outillage | `Dockerfile`, 3 `compose*.yaml`, 2 Caddyfiles, `Makefile`, `.githooks/`, `.github/` (3 workflows, linters, templates), `.editorconfig`, `.gitattributes`, `.dockerignore` | `docker compose config`, `frankenphp validate`, `composer validate --strict`, `composer audit` |
| Documentation | `README.md`, `CHANGELOG.md`, `docs/` (README, overview, 12 ADR, docker-infra), `docs/correctifs/`, `website/` (Docusaurus) | Vérification de chaque lien relatif, cohérence avec le code |

Quatre relectures indépendantes (une par zone) ont produit 94 constats bruts. Chaque constat a ensuite été vérifié contre le code avant décision : **corrigé** quand il s'agissait d'une erreur, d'une incohérence factuelle ou d'un risque de sécurité ; **consigné en 7.6** quand il demandait une décision de conception ou fonctionnelle qui n'appartient pas à un audit.

## 7.2 Synthèse

| Catégorie | Constats retenus | Corrigés | Consignés |
|-----------|------------------|----------|-----------|
| Sécurité | 8 | 7 | 1 |
| Bugs | 16 | 15 | 1 |
| Incohérences factuelles (code, docs) | 21 | 20 | 1 |
| Nettoyage | 14 | 14 | 0 |
| Conception / fonctionnel | 19 | 0 | 19 |

Aucune vulnérabilité connue dans les dépendances (`composer audit`). Aucune mention d'outil tiers dans le dépôt en dehors des deux fichiers hérités du template `symfony-docker`.

## 7.3 Sécurité et stack Docker

| Constat | Fichiers | Correction |
|---------|----------|------------|
| La clé maître de chiffrement avait une valeur par défaut **committée** dans le `compose.yaml` de base, également utilisé par `compose.prod.yaml`. Une production démarrée sans la variable chiffrait tous les documents avec une clé publique. Même chose pour les identifiants MinIO. | `compose.yaml`, `compose.override.yaml`, `compose.prod.yaml` | Plus aucune valeur par défaut dans le fichier de base. Les valeurs jetables de dev sont dans `compose.override.yaml` (chargé automatiquement en dev et en CI). `compose.prod.yaml` déclare `DOCUMENT_MASTER_KEY`, `AWS_S3_ACCESS_KEY`, `AWS_S3_SECRET_KEY` et `POSTGRES_PASSWORD` avec la syntaxe `${VAR:?message}` : Compose refuse de démarrer si l'une manque. Vérifié : `docker compose -f compose.yaml -f compose.prod.yaml config` échoue sans les variables et passe avec. |
| PostgreSQL (5432) et MinIO (9000, 9001) étaient publiés sur l'hôte par le fichier de base, donc en production aussi. | `compose.yaml`, `compose.override.yaml` | Les `ports` sont déplacés dans `compose.override.yaml`. La ligne `"5432"` (port hôte aléatoire, redondante) devient `"${POSTGRES_PORT:-5432}:5432"`, ce qui conserve le fonctionnement de `.env.local`. |
| Le Caddyfile de **production** utilisait encore `publisher_jwt`/`subscriber_jwt` à la racine du bloc `mercure`. Avec le module Mercure actuel, l'image de production quitte au démarrage (« work only in compatibility mode », reproduit avec `frankenphp validate`). | `frankenphp/Caddyfile` | Même bloc `issuer { publisher { jwt … } subscriber { jwt … } }` que `Caddyfile.dev`. Un seul site en prod, donc pas de `name` nécessaire. `frankenphp validate` : « Valid configuration ». |
| Le Caddyfile de production portait `tls internal` et `auto_https disable_redirects`, ajoutés pour le client HTTP de JetBrains en local. En production cela signifie certificat auto-signé, pas de Let's Encrypt, pas de redirection HTTP→HTTPS, à l'inverse de `docs/docker-infra/production.md`. | `frankenphp/Caddyfile` | Les deux directives sont retirées du fichier de production. Elles restent dans `Caddyfile.dev`, où elles ont leur place. |
| L'image amont `dunglas/frankenphp:1-php8.5` est un tag flottant ; c'est lui qui a changé le module Mercure sous les pieds du projet (documents 2 et 7). | `Dockerfile` | Épinglage sur `dunglas/frankenphp:1.13.3-php8.5`, version exactement installée aujourd'hui (`frankenphp version`), existence du tag vérifiée sur le registre. |
| `AWS_S3_ACCESS_KEY`/`SECRET_KEY` étaient codés en dur à `minioadmin` alors que MinIO lit `MINIO_ROOT_USER`/`PASSWORD` : surcharger ces derniers cassait l'accès S3 de PHP. | `compose.override.yaml` | Les identifiants PHP dérivent des mêmes variables que MinIO. |
| Deux workflows faisaient un `actions/checkout` sans `persist-credentials: false` (le workflow CI le faisait déjà). | `.github/workflows/deploy.yml`, `labels.yml` | Option ajoutée. |
| `WrappedDataKey` et `EncryptedPayload` exposaient leur secret dans `var_dump()`, `print_r()` et les logs de debug. | `src/Domain/ValueObject/` | `__debugInfo()` masque la valeur. |
| Le message de l'exception levée par `MinioStorageGateway::store()` recopiait celui de Flysystem (endpoint, bucket), contrairement à `read()` et à l'ADR 0011. | `MinioStorageGateway.php` | Message neutre, exception d'origine conservée dans `previous`. |

## 7.4 Code

### Domaine

| Constat | Fichiers | Correction |
|---------|----------|------------|
| `HolderName` et `StorageSpaceName` validaient la valeur **trimée** mais stockaient la valeur brute : `' Ada '` ≠ `'Ada'` pour `equals()`, et un nom de 255 caractères entouré d'espaces passait la validation puis dépassait `VARCHAR(255)` (erreur PDO, réponse 500). | `HolderName.php`, `StorageSpaceName.php` | La valeur stockée est la valeur trimée. Tests ajoutés (`HolderNameTest`, cas 255 + espaces). |
| Les quatre identifiants (`HolderId`, `StorageSpaceId`, `DocumentId`, `IdentityDocumentId`) acceptaient la chaîne vide dès que le validateur optionnel n'était pas fourni. | `src/Domain/ValueObject/*Id.php` | La chaîne vide est refusée inconditionnellement ; le validateur UUID reste optionnel. `HolderId` reformaté comme ses voisins. Test `HolderIdTest` ajouté. |
| `BirthDate` et `IdentityDocument::create()` comparaient un jour calendaire exprimé dans le fuseau de l'appelant avec « aujourd'hui » en UTC. Les points d'entrée actuels passent déjà des dates UTC, mais le contrat (« today (UTC) ») n'était pas tenu par le type. | `BirthDate.php`, `IdentityDocument.php` | `BirthDate` normalise la valeur en UTC avant comparaison et stockage ; `IdentityDocument` compare les deux jours dans le même fuseau. |
| `.gitkeep` dans des dossiers non vides. | `src/Domain/Port/Gateway/`, `src/Domain/ValueObject/`, `src/Application/UseCase/`, `src/Infrastructure/Gateway/`, `src/Infrastructure/Presentation/ApiPlatform/` | Supprimés. Ceux des dossiers réellement vides (`Event/`, `Port/Clock/`, `Port/Event/`, `Port/Notification/`, `Application/Dto/`) sont conservés, voir 7.6. |

### Application et Infrastructure

| Constat | Fichiers | Correction |
|---------|----------|------------|
| `SodiumCipherGateway::decrypt()` : `splitNonce()` découpait sans vérifier la longueur. Une clé enveloppée ou un payload plus court que nonce + MAC faisait lever `SodiumException` (et non retourner `false`), donc une 500 non gérée. | `SodiumCipherGateway.php` | Contrôle de longueur minimale avec message explicite, `SodiumException` convertie en `RuntimeException`, `sodium_memzero` dans un `finally`. Trois tests ajoutés (clé tronquée, payload tronqué, payload corrompu). |
| Onze types Doctrine fondés sur `StringType` retournaient silencieusement `NULL` pour une valeur inattendue, ce qui se traduisait plus loin par une violation `NOT NULL` sans rapport avec la cause. `BirthDateType` faisait déjà le bon choix. | `src/Infrastructure/Persistence/Doctrine/Type/*Type.php` (12 fichiers) | Chaque branche inattendue lève `Doctrine\DBAL\Types\Exception\InvalidType` avec les types acceptés. Import inutile de `StorageSpaceId` dans `HolderIdType` et double espace dans `HolderNameType` corrigés au passage. |
| `StorageSpaceRepository::update()` exécutait du SQL brut (noms de table et de colonnes dupliqués hors du mapping), ignorait le nombre de lignes affectées (0 ligne = succès silencieux) et vidait **toute** l'unité de travail avec `clear()`. `findAll()` redéfinissait inutilement la méthode parente. | `StorageSpaceRepository.php` | Requête DQL sur l'entité, `StorageSpaceNotFoundException` si aucune ligne, seule l'instance périmée du même agrégat est détachée. `findAll()` supprimée. |
| `GET /v1/health` rapportait `environment: "dev"` partout : les deux arguments du use case avaient des valeurs par défaut et n'étaient liés nulle part. | `CheckSystemStatusUseCase.php`, `config/services.yaml` | Arguments liés à `%kernel.environment%` et `%api_platform.version%`, plus de valeur par défaut. Classe `final readonly`, constante avec visibilité. Test `HealthCheckApiTest` ajouté (vérifie `test` et `1.0.0`). |
| `StoreDocumentUseCase` construisait `HolderId` sans `IdValidatorInterface`, à l'inverse de `GetHolderUseCase`, `GetStorageSpaceUseCase` et `ChangeStorageSpaceStatusUseCase`. | `StoreDocumentUseCase.php` | Validateur injecté et utilisé. Test unitaire : un identifiant mal formé est rejeté avant toute lecture en base. |
| `GET /v1/storage-spaces` documentait `page` et `itemsPerPage` dans OpenAPI alors que le use case retourne toute la table. | `StorageSpaceResource.php` | `paginationEnabled: false` sur l'opération, le temps qu'une pagination réelle soit implémentée. |
| `DocumentResource::$file` (propriété publique) était resérialisé dans la réponse 201 (`"file": null`) et dans le schéma de sortie. | `DocumentResource.php` | `#[ApiProperty(readable: false)]`. |
| `App\: resource: '../src/'` sans `exclude` : le kernel, les entités, les value objects, les exceptions et les DTO `*Command` étaient enregistrés comme services. | `config/services.yaml` | Liste `exclude` conforme à la recette Symfony. `lint:container` et la suite de tests confirment qu'aucun service utile n'est perdu. |
| `src/Infrastructure/Service/Comparator.php` : énumération sans aucune utilisation, sans `strict_types`. | — | Supprimée. |

## 7.5 Tests et outillage

| Constat | Fichiers | Correction |
|---------|----------|------------|
| `make vendor` installait avec `--no-dev --no-scripts` : le `make test` suivant échouait (`vendor/bin/deptrac: not found`). Le Dockerfile de prod fait déjà son propre `--no-dev`. | `Makefile` | Options retirées. |
| `make migrate-all` enchaînait `migration-diff`, **`migration-generate`** (fichier vide) et `migrate` : chaque appel committait une migration vide. Cible absente de `.PHONY` et de l'aide. | `Makefile` | `migrate-all: migration-diff migrate`, aide et `.PHONY` complétés. |
| `make test-db` n'exécutait pas `doctrine:schema:validate`, contrairement à la CI. | `Makefile` | Étape ajoutée. |
| Une seule suite PHPUnit : impossible de lancer les tests unitaires sans PostgreSQL. Bloc `<extensions>` vide. | `phpunit.dist.xml` | Suites `unit` et `api` (`bin/phpunit --testsuite unit`). Bloc vide supprimé. `bin/phpunit` sans argument exécute toujours tout. |
| `composer.json` : `league/flysystem-*` en `*` (avertissement `composer validate --strict`), `ext-sodium` utilisé mais non déclaré, `symfony/validator` et `symfony/property-info` utilisés directement mais seulement transitifs. | `composer.json`, `composer.lock` | Contraintes `^3.35` / `^3.7`, `ext-sodium` dans `require` et dans `config.platform`, les deux composants Symfony déclarés. `composer validate --strict` sans avertissement, `composer audit` sans alerte. |
| Le stockage de test (`var/test_storage`, adaptateur Flysystem local) n'était jamais nettoyé : un objet chiffré de 236 Ko par exécution. | `tests/Api/DocumentApiTest.php` | Suppression du dossier dans `setUp()`. |
| `tests/http/v1/documents/store-document.http` : deux requêtes en `application/json` vers une opération `multipart/form-data` (415 garanti), champs `metadata.*` ignorés par l'API, `Authorization` non commenté ; la première requête n'envoyait pas `holderId` (422). | `store-document.http` | Deux requêtes : dépôt complet (avec la partie `holderId` fournie par `holders/post.http`, assertion 201) et dépôt sans holder (assertion 422). |
| `tests/http/v1/Holders/post.http` interrogeait un UUID qui n'existe que dans la base de **test** (fixture PHPUnit) ; dossier `Holders/` seul en majuscule ; pas d'assertions. | `tests/http/v1/holders/post.http` | POST avec `client.global.set("holderId")`, GET chaîné, GET inconnu (404), assertions. Dossier renommé `holders/`. |
| `tests/http/v1/compliance/audit-log.http` et `tests/http/v1/documents/consult-document.http` ciblaient des endpoints qui n'existent pas (404 garanti, assertion 200). | — | Supprimés. `GET /v1/documents/{id}` reste listé comme « Planned » dans le CHANGELOG ; le fichier sera à recréer avec l'endpoint. |
| CI : Deptrac et PHPStan tournaient sur PHP 8.4 alors que l'image exécute PHP 8.5. | `.github/workflows/ci.yaml` | `php-version: '8.5'`. |
| `.editorconfig` ne couvrait que `.github/**/*.yaml` ; `deploy.yml` et `labels.yml` échappaient à la règle d'indentation. | `.editorconfig` | Motif `.yml` ajouté. |
| `.dockerignore` laissait `website/` (sources Docusaurus, éventuel `node_modules/`), `.devcontainer/`, `.vscode/`, `.githooks/` entrer dans le contexte de build. | `.dockerignore` | Exclus. |
| `.gitattributes` ne déclarait pas `*.jpg` binaire (`website/static/img/docusaurus-social-card.jpg`). | `.gitattributes` | `*.gif`, `*.jpg`, `*.jpeg` ajoutés. |

## 7.6 Documentation

| Constat | Fichiers | Correction |
|---------|----------|------------|
| `README.md` liait `docs/architecture/workflows/` (inexistant) et recommandait `docker compose up`, qui ignore `.env.local` (cause du conflit de port du document 1). | `README.md` | Lien remplacé par les documents correctifs ; Quick Start en `make build` / `make up` / `make down` avec l'explication de `.env.local`. |
| `docs/README.md` liait trois fichiers inexistants (`workflows/ingestion.md`, `workflows/consultation.md`, `api/endpoints.md`) et n'énumérait que les ADR 0001 à 0008. | `docs/README.md` | Liens morts remplacés (OpenAPI servie sur `/api`), ADR 0009 à 0012 ajoutés, lien vers `docs/correctifs/`. |
| ADR 0009 se terminait par une balise `</content>` parasite ; ADR 0008 contenait plusieurs coquilles (« Infrasctructure », « directtly », « emebeded », « exepose », « outside word », « end description »). | `docs/architecture/adr/0008-*.md`, `0009-*.md` | Corrigés, décisions inchangées. |
| `docs/architecture/overview/index.md` montrait une arborescence antérieure à l'ADR 0008 (`Infrastructure/ApiPlatform/`, `Domain/Service/`), sans `Storage/`, `Service/`, `Presentation/`. | `overview/index.md` | Arborescence alignée sur `find src -type d`, dossiers réservés signalés comme vides. |
| `CHANGELOG.md` citait des value objects inexistants (`OwnerId`, `DocumentMetadata`), plaçait `StorageKey` dans le domaine, datait la 0.1.0 d'un « squelette Symfony 7 & API Platform 3 » alors que le premier commit exigeait déjà Symfony 8.1 et API Platform 4.3, et ne mentionnait ni `StorageSpace` ni `IdentityDocument`. | `CHANGELOG.md` | Corrigé ; entrées `Changed`, `Fixed` et `Security` de cet audit ajoutées sous `[Unreleased]`. |
| `docs/correctifs` : accents perdus sur onze lignes du journal et dans les commentaires de `Caddyfile.dev` (recopiés dans le document 2) ; première ligne du journal contradictoire avec la ligne 05:40 ; document 3 affirmait une contrainte `^4` sur `doctrine/dbal` dans `composer.json`, qui n'y a jamais été dépendance directe ; checklist 4.9 non cochée alors que le document 6 l'avait traitée ; table des commits du README incomplète. | `JOURNAL.md`, `Caddyfile.dev`, `02-*.md`, `03-*.md`, `04-*.md`, `README.md` | Tous corrigés. |

## 7.7 Vérification

| Étape | Résultat |
|-------|----------|
| `docker compose config` (dev) | OK |
| `docker compose -f compose.yaml -f compose.prod.yaml config` sans secrets | Refusé : « required variable POSTGRES_PASSWORD is missing a value » (comportement voulu) |
| Idem avec les secrets fournis | OK |
| `frankenphp validate` sur `Caddyfile` (prod) et `Caddyfile.dev` | « Valid configuration » pour les deux ; l'ancien `Caddyfile` de prod échouait (« compatibility mode ») |
| `composer validate --strict`, `composer audit` | Valide, aucune alerte |
| Deptrac | 0 violation |
| PHPStan niveau max | 0 erreur |
| PHPUnit | 107 tests, 200 assertions, 0 échec (93 tests avant l'audit) |

## 7.8 Points de conception non tranchés

Classés par priorité. Aucun n'est une erreur au sens strict ; chacun demande une décision du propriétaire du projet.

**Priorité haute (sécurité, intégrité)** : les cinq points ci-dessous ont été traités le jour même, voir le [document 8](08-corrections-prioritaires.md).

1. **Taille et type des fichiers déposés.** `#[Assert\File]` sur `DocumentResource::$file` n'a ni `maxSize` ni `mimeTypes` ; `DocumentProcessor` lit tout le contenu en mémoire avant hachage et chiffrement. Seul `upload_max_filesize` (2 Mo par défaut, non configuré dans `frankenphp/conf.d/`) borne l'entrée. Décider la limite métier et les formats acceptés, puis les porter dans la contrainte et dans `10-app.ini`.
2. **Ordre stockage / persistance dans `StoreDocumentUseCase`.** L'objet chiffré est écrit dans le bucket avant `documents->add()`. Un échec de l'INSERT laisse un objet orphelin. Ajouter une compensation (suppression dans le port de stockage, qui n'a pas encore de méthode `delete()`) ou inverser l'ordre.
3. **Transitions d'état de `Holder`.** `requestErasure()` et `anonymize()` retournent une nouvelle instance que rien ne peut persister : `HolderRepositoryInterface` n'a ni `save()` ni `update()`. Le parcours RGPD d'effacement est donc inatteignable depuis l'application. Il faudrait une méthode de mise à jour (sur le modèle de `StorageSpaceRepository::update()`) et un use case.
4. **Anonymisation incomplète.** `anonymize()` conserve `birthDate` et `consentCollectedAt`, et `HolderResource` expose `birthDate` sans condition. Une date de naissance complète sur un identifiant stable reste quasi-identifiante. Options : `?BirthDate` mis à `null`, ou troncature à l'année.
5. **Clé enveloppée non liée au document.** `SodiumCipherGateway` n'utilise pas de données additionnelles authentifiées : une paire clé/ciphertext peut être échangée entre deux documents. La vérification de `FileHash` à la lecture doit donc rester obligatoire quand `GET /v1/documents/{id}` sera implémenté.

**Priorité moyenne (cohérence du modèle)**

6. **Vocabulaire des repositories.** `save`/`add` pour l'insertion, `get`/`findById` pour la lecture par identifiant, `get()` retournant `null` là où la convention attend une exception. Choisir un vocabulaire unique pour les quatre ports.
7. **`StorageGatewayInterface` manipule des `string`** alors que `EncryptedPayload` existe précisément pour garantir qu'on ne stocke jamais du clair. Typer `store()`/`read()` avec le value object.
8. **Validateur d'identifiant optionnel.** `?IdValidatorInterface $validator = null` sur les quatre identifiants contredit l'ADR 0004 (« l'invariant est validé dans le constructeur »). `CountryCode` et `StorageSpaceCode` valident eux-mêmes avec une expression régulière ; les identifiants pourraient faire de même, ce qui rendrait `IdValidatorInterface` inutile dans le domaine.
9. **Générateurs d'identifiants.** `GeneratorInterface` (retourne `string`, utilisé par `Holder` seul) coexiste avec trois `XxxIdGeneratorInterface` typés. Choisir un modèle. `IdentifierInterface` est un marqueur vide implémenté par deux identifiants sur quatre.
10. **`StorageSpace`** a un constructeur public et des propriétés publiques, contrairement aux trois autres agrégats (constructeur privé, accesseurs) ; `create()` peut être contourné. `withStatus()` accepte toute transition (`archived → active`), et le test `testItRejectsAnInvalidStatusTransition` teste en réalité une valeur inconnue.
11. **`HolderAlreadyExistsException`** reste déclenchée sur une collision d'UUID v7 fraîchement généré (un aller-retour en base par POST pour un cas théorique) et mappée sur un 409 que le client ne peut jamais provoquer. Soit définir une vraie règle d'unicité (nom + date de naissance, pièce d'identité), soit retirer le contrôle.
12. **Horloge non injectable.** Toutes les entités appellent `new \DateTimeImmutable('now')`. `src/Domain/Port/Clock/` est vide. Un port `ClockInterface` (ou `symfony/clock`) rendrait le temps testable et supprimerait les tests dépendants de minuit UTC ou de la date fixe `2035-01-31`.
13. **`SystemStatus`** dans le domaine : trois chaînes non validées sans invariant, pour un modèle de lecture technique. À déplacer dans `Application/UseCase/CheckSystemStatus/`.
14. **Casse des énumérations.** `IdentityDocumentType` vaut `'Passport'`/`'NationalCard'` quand `HolderStatus` et `StorageSpaceStatus` sont en `snake_case`. Changer implique une migration de données et un changement de contrat d'API.
15. **`IdentityDocumentNumber`** refuse minuscules, espaces et tirets (`^[A-Z0-9]{1,20}$`), présents sur de vrais documents. Décider si la normalisation appartient au value object ou au use case.
16. **Liens entre agrégats.** `IdentityDocument` ne porte pas de `HolderId`, `Document` pas de `StorageSpaceId`, `StorageSpace` n'est référencé par rien. Si ces liens sont voulus, ils manquent ; sinon le modèle devrait le dire.

**Priorité basse (outillage, documentation)**

17. **PHPStan n'analyse pas `tests/`** et cette analyse ne passerait pas aujourd'hui (fermetures non typées, `array` issus de `toArray()`). Ajouter `tests` à `paths` avec `phpstan/phpstan-phpunit`, puis corriger. `containerXmlPath` pointe vers le conteneur **dev**, qui doit être réchauffé avant l'analyse.
18. **Deptrac** : aucune couche ne couvre les namespaces vendeurs (`League\`, `Aws\`, `Psr\`), donc un import vendeur dans `App\Domain` serait « non couvert » et non une violation. Ajouter `--report-uncovered --fail-on-uncovered` ou une couche `Vendor`.
19. **`ChangeStorageSpaceStatusProcessor`** ne passe pas par le validateur Symfony comme les quatre autres processors ; `StorageSpaceResource::$status` n'a pas d'`Assert\Choice`.
20. **Sorties OpenAPI** : les champs de sortie (`id`, `status`, `createdAt`, `fileHash`…) ne portent pas `#[ApiProperty(writable: false)]`, le corps multipart de `POST /v1/documents` est documenté avec le schéma de la ressource. Les providers `GET` lèvent `NotFoundHttpException` là où l'ADR 0007 demande de retourner `null` ; l'ADR 0011 décrit l'inverse : les deux ADR doivent être réconciliés.
21. **Hook pre-commit** : la branche sans `make` (cas fréquent sous Windows) ne lance ni PHPStan ni `test-db`, et vise `127.0.0.1:5432` alors que `.env.local` publie PostgreSQL sur 5434. Faire exécuter la suite `unit` par le hook et laisser la suite `api` à la CI.
22. **`.gitattributes`** : `composer.lock merge=ours` n'est actif que si l'utilisateur a configuré ce pilote, et quand il l'est il garde silencieusement un lock incohérent. `merge=binary` force une résolution explicite.
23. **Dossiers réservés vides** (`Domain/Event`, `Port/Clock`, `Port/Event`, `Port/Notification`, `Application/Dto`) : structure spéculative. Les implémenter (voir 12) ou les supprimer.
24. **ADR périmés** : l'ADR 0009 décrit `Document` avec `StorageKey` et sans `HolderId` (l'inverse du code) ; les ADR 0004 et 0005 prennent `RegulatoryScope` en exemple, fonctionnalité retirée. Un addendum suffirait ; les ADR n'ont pas été modifiés ici au-delà des coquilles.
25. **`api_platform.yaml`** : Swagger UI et `/api/docs` sont servis en production (choix défendable pour une API open source, à confirmer). `.env` : `DEFAULT_URI` en `http://` contre `https://` dans Compose ; `CORS_ALLOW_ORIGIN` n'inclut pas `compliance-store.loc`.
26. **`.githooks/pre-commit`** indenté à 4 espaces alors que `.editorconfig` impose des tabulations aux scripts shell ; `zizmor.yaml` pourrait être ignoré par super-linter qui attend `zizmor.yml` par défaut.
27. **Couverture de tests** : `CheckSystemStatusUseCase`, `CreateHolderUseCase`, `GetHolderUseCase`, `GetStorageSpaceUseCase`, `ListStorageSpacesUseCase` n'ont pas de test unitaire ; `MinioStorageGateway`, les repositories, les générateurs et le validateur ne sont couverts qu'indirectement ; aucun test ne déchiffre un document réellement écrit ; les tests négatifs d'API n'affirment que le code HTTP.

## 7.9 Commit

Un seul commit porte l'ensemble de l'audit, après validation du hook `make test` :

| Commit | Branche | Message |
|--------|---------|---------|
| `1f7a07e` | `main` | `fix: project-wide audit (compose secrets, prod Caddyfile, Doctrine types, cipher robustness, tooling, docs)` |
