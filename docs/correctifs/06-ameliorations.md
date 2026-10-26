# 6. Améliorations : dépendances, persistance de `Holder`, consultation

Phase d'amélioration menée le 24 octobre 2026 après les correctifs des documents 2 à 4. Elle traite les points restants listés en 4.9 et met les dépendances à jour. Tout est validé par `make test` : Deptrac 0 violation, PHPStan 0 erreur, PHPUnit 93 tests et 175 assertions (88 tests avant cette phase).

## 6.1 Mise à jour des dépendances

Dependabot avait proposé plusieurs mises à jour sur le dépôt d'origine, toutes dans les contraintes de `composer.json`. Elles ont été appliquées en une passe, dans le conteneur :

```
composer update "symfony/*" "phpstan/*" doctrine/doctrine-bundle doctrine/doctrine-migrations-bundle deptrac/deptrac phpunit/phpunit --with-all-dependencies
composer update doctrine/orm
```

| Paquet | Avant | Après |
|--------|-------|-------|
| `symfony/*` (framework-bundle, http-client, runtime, console, uid, yaml, twig-bundle, browser-kit, css-selector…) | 8.1.0 à 8.1.7 | 8.1.9 |
| `doctrine/orm` | 3.6.9 | 3.7.5 |
| `doctrine/dbal` | 4.5.0 (document 3) | 4.5.0 |
| `doctrine/doctrine-bundle` | 3.3.1 | 3.3.2 |
| `doctrine/doctrine-migrations-bundle` | 4.0.0 | 4.0.1 |
| `phpstan/phpstan` | 2.2.8 | 2.3.1 |
| `phpstan/phpstan-symfony` | 2.0.20 | 2.1.0 |
| `phpunit/phpunit` | 13.3.3 | 13.4.3 |
| `deptrac/deptrac` | 4.7.1 | 4.7.2 |
| `api-platform/core` | 4.3.18 | 4.3.18, inchangé |

`doctrine/orm` 3.7 était bloqué dans le `dependabot.yaml` d'origine parce qu'il exige DBAL 4.5 ; la montée de DBAL (document 3) lève ce blocage.

**Non fait, volontairement.** `api-platform/core` 5.0 est une version majeure avec des changements de contrat (providers, processors, sérialisation) qui demandent une migration dédiée. `super-linter` v9 dans la CI est aussi une version majeure, laissée en v8.

## 6.2 Persistance complète de l'agrégat `Holder`

### Problème

`Holder.orm.xml` ne mappait que `id`, `name` (longueur 255) et `createdAt`. La migration `Version20261024020529` créait `name VARCHAR(20)`. L'entité porte pourtant `birthDate`, `status`, `consentCollectedAt`, `erasureRequestedAt` et `anonymizedAt`. Conséquences : un `Holder` relu depuis la base avait des propriétés readonly non initialisées, et un nom de plus de 20 caractères était refusé par PostgreSQL en dev.

### Correction

**Nouveau type Doctrine `birth_date`** (`src/Infrastructure/Persistence/Doctrine/Type/BirthDateType.php`), enregistré dans `config/packages/doctrine.yaml`. Il stocke un `BirthDate` en SQL `DATE` et le reconstruit à la lecture. Il étend `Doctrine\DBAL\Types\Type` directement : `DateImmutableType` fixe le type de retour de `convertToPHPValue()` à `?DateTimeImmutable`, ce qui interdit de retourner le value object (erreur fatale PHP de compatibilité de déclaration, rencontrée lors du premier essai).

**Mapping complet** dans `Holder.orm.xml` :

| Champ | Type Doctrine | Colonne | Nullable |
|-------|---------------|---------|----------|
| `id` | `holder_id` | `id` | non |
| `name` | `holder_name`, longueur 255 | `name` | non |
| `birthDate` | `birth_date` | `birth_date` | non |
| `status` | `string` + `enum-type HolderStatus`, longueur 20 | `status` | non |
| `createdAt` | `datetime_immutable` | `created_at` | non |
| `consentCollectedAt` | `datetime_immutable` | `consent_collected_at` | oui |
| `erasureRequestedAt` | `datetime_immutable` | `erasure_requested_at` | oui |
| `anonymizedAt` | `datetime_immutable` | `anonymized_at` | oui |

Le mapping de `status` suit celui de `StorageSpace.status` (`enum-type`). Doctrine hydrate les propriétés `readonly` privées par réflexion, comme il le fait déjà pour `StorageSpace`.

**Déplacement** de `HolderRepository` vers `src/Infrastructure/Persistence/Doctrine/`, à côté des autres repositories Doctrine. Le namespace devient `App\Infrastructure\Persistence\Doctrine`. L'autowiring par ressource (`App\: resource: '../src/'`) ne demande aucune configuration.

## 6.3 Rattachement persistant du `Document` à son holder

### Problème

`Document` portait `holderId` depuis le commit `2eb6ec5`, mais `Document.orm.xml` ne le mappait pas et la table n'avait pas de colonne. Un document relu depuis la base aurait eu un `holderId` non initialisé. Rien ne vérifiait non plus que le holder existe.

### Correction

- `Document.orm.xml` mappe `holderId` (type `holder_id`, colonne `holder_id`, longueur 36) avec un index `idx_document_holder_id`.
- Pas de clé étrangère SQL : conformément au commentaire de l'entité, la relation entre agrégats est portée par l'identifiant, et l'intégrité est garantie dans la couche Application. Une clé étrangère non déclarée dans le mapping aurait de plus créé un écart permanent entre `doctrine:schema:validate` et la base.
- `StoreDocumentUseCase` reçoit `HolderRepositoryInterface` et vérifie l'existence du holder **avant** tout chiffrement ou écriture sur le stockage. Un holder inconnu lève `HolderNotFoundException` (nouvelle exception de domaine).
- `DocumentProcessor` traduit `HolderNotFoundException` en 422 : la ressource créée est le document, l'URI demandée existe, un 404 serait trompeur.

### Tests

- `StoreDocumentUseCaseTest` : le holder est fourni par un stub ; un nouveau test vérifie qu'un holder inconnu bloque avant `encrypt()` et `store()`.
- `DocumentApiTest` : le `setUp` enregistre un `Holder` en base via l'EntityManager (ce qui exerce le mapping complet), et un nouveau test vérifie le 422 pour un holder inconnu.

## 6.4 Migration `Version20261024071500`

```sql
ALTER TABLE holder ALTER name TYPE VARCHAR(255);
ALTER TABLE holder ADD birth_date DATE NOT NULL;
ALTER TABLE holder ADD status VARCHAR(20) NOT NULL;
ALTER TABLE holder ADD consent_collected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
ALTER TABLE holder ADD erasure_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
ALTER TABLE holder ADD anonymized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
ALTER TABLE document ADD holder_id VARCHAR(36) NOT NULL;
CREATE INDEX idx_document_holder_id ON document (holder_id);
```

Les colonnes `NOT NULL` n'ont pas de valeur par défaut raisonnable pour des lignes existantes. Plutôt que d'inventer une date de naissance, la migration **refuse de s'exécuter** (`abortIf`) si `holder` ou `document` contient des lignes, avec un message explicite. Les deux tables datent de la même journée et étaient vides en dev. Le `down()` est complet.

Après migration, `doctrine:schema:validate` confirme en dev que la base est en phase avec le mapping. En test, `make test-db` reconstruit le schéma depuis le mapping.

## 6.5 Consultation d'un holder : `GET /v1/holders/{id}`

Ajout sur le modèle exact de `GET /v1/storage-spaces/{id}` :

| Couche | Fichier | Rôle |
|--------|---------|------|
| Application | `UseCase/GetHolder/GetHolderUseCase.php` | Valide l'UUID (`IdValidatorInterface`), charge le holder, lève `HolderNotFoundException` |
| Infrastructure | `State/GetHolderProvider.php` | Traduit l'exception et un id invalide en 404, retourne `HolderResource::fromEntity()` |
| Infrastructure | `Resource/HolderResource.php` | Opération `Get` déclarée, champs de sortie `consentCollectedAt`, `erasureRequestedAt`, `anonymizedAt` ajoutés |

`HolderApiTest` gagne trois tests : lecture d'un holder créé (compare `id`, `holderName`, `birthDate`, `status`, `createdAt` entre la réponse du POST et celle du GET), 404 pour un id inconnu, 404 pour un id qui n'est pas un UUID. Le test de lecture est la preuve que le mapping complet fonctionne : le `Holder` est relu depuis PostgreSQL et resérialisé.

## 6.6 Nettoyages

- `declare(strict_types=1)` ajouté dans `CreateHolderCommand`, `HolderId`, `IdentifierInterface`, `HolderAlreadyExistsException`, `SymfonyUuidGenerator`.
- `SymfonyUuidGenerator` devient `final readonly`, comme les autres adaptateurs du dossier `Service/`.
- `HolderAlreadyExistsException` devient `final`, son message corrigé (« with id » au lieu de « with string »).
- `tests/http/v1/Holders/post.http` envoie une vraie date de naissance et un exemple de `GET`.
- `CHANGELOG.md` : entrées `Added` et `Changed` sous `[Unreleased]`.

## 6.7 Vérification

| Étape | Résultat |
|-------|----------|
| `doctrine:migrations:migrate` (dev) | 1 migration, 8 requêtes SQL |
| `doctrine:schema:validate` (dev) | Mapping correct, base en phase |
| Deptrac | 0 violation, 343 dépendances autorisées |
| PHPStan niveau max | 0 erreur |
| PHPUnit | 93 tests, 175 assertions, 0 échec |

## 6.8 Points encore ouverts

- `HolderAlreadyExistsException` reste déclenchée sur une collision d'UUID v7 fraîchement généré, cas théorique. Une règle métier de dédoublonnage (nom + date de naissance, ou pièce d'identité) relève d'une décision fonctionnelle.
- Les transitions `requestErasure()` et `anonymize()` de `Holder` existent dans le domaine mais ne sont exposées par aucun endpoint ni persistées par aucune méthode de repository (il faudrait un `update()` comme sur `StorageSpaceRepository`).
- `GET /v1/documents/{id}` (consultation déchiffrée) est toujours marqué « Planned » dans le CHANGELOG.
- Passage à API Platform 5 et super-linter 9 : à planifier séparément.
