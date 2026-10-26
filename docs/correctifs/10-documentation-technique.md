# Compliance Store : documentation technique

*Ce document décrit comment le code réalise ce que [09-presentation-generale.md](09-presentation-generale.md) explique en termes simples. Il s'adresse aux développeurs, intégrateurs et exploitants. Les choix d'architecture sont justifiés en détail dans les [ADR](../architecture/adr/).*

État de référence : PHP 8.5, Symfony 8.1, API Platform 4.3, PostgreSQL 16, 159 tests PHPUnit, PHPStan niveau max, Deptrac 0 violation.

---

## 1. Vue d'ensemble

Compliance Store est une API REST sans état (stateless) qui expose six ressources sous `/api/v1` : porteurs, documents, pièces d'identité, espaces de stockage, événements d'audit, santé. Elle s'appuie sur trois services d'infrastructure :

| Service | Rôle | Image Docker (dev) |
|---------|------|--------------------|
| PHP / FrankenPHP / Caddy | Exécute l'application en mode worker, termine TLS, applique les limites de taille des requêtes | `dunglas/frankenphp:1.13.3-php8.5` |
| PostgreSQL 16 | Métadonnées, clés chiffrées, clients d'API, journal d'audit | `postgres:16-alpine` |
| MinIO (ou tout S3) | Stockage des fichiers chiffrés | `pgsty/minio` |

```text
  Client (back-office, batch)
        │  HTTPS + Authorization: Bearer cs_...
        ▼
  Caddy ── refuse > 16 MiB (413) ── FrankenPHP (PHP worker)
        │
        ▼
  Symfony Security ── firewall `api` ── ApiKeyTokenHandler ── 401 / 403
        │
        ▼
  API Platform ── Resource + Provider/Processor (Infrastructure/Presentation)
        │
        ▼
  Use case (Application) ── entités, value objects, règles (Domain)
        │                                  │
        ▼                                  ▼
  Doctrine ── PostgreSQL          CipherGateway (Libsodium) ── StorageGateway (Flysystem) ── MinIO/S3
```

---

## 2. Architecture du code

Le code suit la **Clean Architecture** : trois couches dont les dépendances pointent toutes vers l'intérieur. Deptrac vérifie cette règle à chaque commit.

```text
src/
├── Domain/          Règles métier pures. Aucun import Symfony, Doctrine ou API Platform.
│   ├── Entity/      Holder, Document, IdentityDocument, StorageSpace, AuditEvent
│   ├── ValueObject/ HolderId, HolderName, BirthDate, FileHash, WrappedDataKey, EncryptedPayload,
│   │                HolderStatus, StorageSpaceStatus, IdentityDocumentType, AuditAction…
│   ├── Exception/   HolderNotFoundException, DocumentIntegrityException, InvalidHolderStatusTransitionException…
│   └── Port/        Interfaces attendues par le domaine :
│       ├── Gateway/     CipherGatewayInterface, StorageGatewayInterface
│       ├── Repository/  HolderRepositoryInterface, DocumentRepositoryInterface, AuditEventRepositoryInterface…
│       └── Service/     AuditTrailInterface, générateurs d'identifiants, IdValidatorInterface
├── Application/
│   └── UseCase/     15 cas d'usage, un dossier chacun : Command (entrée) + UseCase (orchestration)
└── Infrastructure/  Implémentations concrètes des ports
    ├── Persistence/ Repositories Doctrine, mappings XML, types DBAL pour les value objects
    ├── Presentation/ Resources API Platform, State Providers (lectures) et State Processors (écritures)
    ├── Gateway/     SodiumCipherGateway (chiffrement d'enveloppe)
    ├── Storage/     MinioStorageGateway (Flysystem S3), StorageKey (`documents/<uuid>`)
    ├── Security/    ApiClient, ApiKeyTokenHandler, ApiKeyEntryPoint, mapping Doctrine dédié
    ├── Audit/       SecurityAwareAuditTrail (acteur = client d'API authentifié)
    ├── Console/     app:api-client:create, app:api-client:revoke
    └── Service/     Génération d'UUID v7, validation d'identifiants
```

### 2.1 Principes appliqués

- **Pas de primitives dans le domaine** (ADR 0004) : chaque donnée métier est un value object validé à la construction. Un `HolderName` vide ou un `FileHash` mal formé ne peuvent pas exister.
- **Un cas d'usage par opération** (ADR 0003) : un Provider ou Processor API Platform ne contient aucune logique, il convertit la requête en Command, appelle le use case et traduit les exceptions du domaine en codes HTTP.
- **Validation en deux temps** (ADR 0005) : les DTO d'entrée sont validés par les contraintes Symfony, puis le domaine revalide via ses value objects. Une erreur de surface donne 422, une erreur de règle métier donne 404, 409 ou 422 selon le cas.
- **Entités immuables ou à transitions explicites** : `Document`, `IdentityDocument`, `StorageSpace` et `AuditEvent` sont `readonly` ; `Holder` expose uniquement `requestErasure()` et `anonymize()`, qui vérifient la transition de statut.

---

## 3. Modèle de données

Six tables PostgreSQL, créées par 12 migrations Doctrine (`migrations/`).

### 3.1 `holder` : la personne concernée

| Colonne | Type | Remarque |
|---------|------|----------|
| `id` | uuid | UUID v7 généré par l'application |
| `name` | holder_name | Vaut `anonymized` après anonymisation |
| `birth_date` | birth_date (date) | Nullable depuis l'anonymisation |
| `status` | enum | `active` → `erasure_requested` → `anonymized` |
| `created_at`, `consent_collected_at`, `erasure_requested_at`, `anonymized_at` | timestamptz | Les trois dernières nullables |

Transitions autorisées, vérifiées dans `Holder` et testées dans `HolderTest` :

```text
active ──requestErasure()──► erasure_requested ──anonymize()──► anonymized
```

Toute autre transition lève `InvalidHolderStatusTransitionException` (HTTP 409).

### 3.2 `document` : la fiche d'un fichier chiffré

| Colonne | Type | Remarque |
|---------|------|----------|
| `id` | uuid | Identifiant, aussi utilisé comme clé objet `documents/<id>` dans S3 |
| `holder_id` | uuid | Référence le porteur |
| `file_hash` | file_hash | SHA-256 hexadécimal du **contenu en clair** |
| `wrapped_data_key` | wrapped_data_key | Clé de données du document, chiffrée par la clé maître (nonce + ciphertext, base64) |
| `created_at` | timestamptz | |

Le fichier chiffré lui-même n'est **pas** en base : il est dans le stockage objet.

### 3.3 `identity_document`

`id`, `type` (`Passport` ou `NationalCard`), `country` (code ISO), `number`, `expires_at`, `created_at`. Contrainte d'unicité sur (type, pays, numéro). Une date d'expiration antérieure à aujourd'hui (UTC) est refusée à la création (`IdentityDocumentExpiredException`). **Aucune colonne ne relie cette table à `holder` ou `document`.**

### 3.4 `storage_space`

`id`, `code` (unique), `name`, `status` (`active`, `inactive`, `archived`), `created_at`. **Aucune colonne de `document` ne référence un espace de stockage.**

### 3.5 `audit_event` : journal en ajout seul

| Colonne | Remarque |
|---------|----------|
| `id` | UUID v7 |
| `action` | `holder_registered`, `holder_erasure_requested`, `holder_anonymized`, `document_stored`, `document_consulted` |
| `subject_id` | Identifiant du porteur ou du document concerné (indexé) |
| `actor` | Nom du client d'API, ou `system` hors requête (tronqué à 100 caractères) |
| `occurred_at` | UTC (indexé) |

Le repository n'expose que `add()` et `findLatest()` : aucune mise à jour ni suppression n'est possible par l'application.

### 3.6 `api_client` : les appelants

`id`, `name` (unique), `key_hash` (SHA-256 de la clé, unique), `roles` (json), `created_at`, `revoked_at` (nullable). Mapping Doctrine séparé (`src/Infrastructure/Security/Mapping/`) : cette table est une préoccupation d'infrastructure, le domaine ne la connaît pas (ADR 0013).

---

## 4. Les cas d'usage, un par un

| Cas d'usage | Entrée | Règles appliquées | Sortie / erreurs |
|-------------|--------|-------------------|------------------|
| `CreateHolder` | nom, date de naissance | Id généré et vérifié unique (`HolderAlreadyExistsException`), value objects valides | Holder actif ; audit `holder_registered` |
| `GetHolder` | id | Existence | Holder ou `HolderNotFoundException` |
| `RequestHolderErasure` | id | Statut `active` requis | Statut `erasure_requested` ; audit |
| `AnonymizeHolder` | id | Statut `erasure_requested` requis ; pour chaque document : suppression S3 puis suppression en base ; puis nom → `anonymized`, date de naissance → null | Audit `holder_anonymized` |
| `StoreDocument` | holderId, contenu du fichier | Porteur existant **et actif** ; hash SHA-256 du clair ; chiffrement ; écriture S3 ; insertion en base ; en cas d'échec de l'insertion, suppression compensatoire de l'objet S3 et relance de l'erreur | Document ; audit `document_stored` |
| `GetDocument` | id | Existence | Métadonnées ou `DocumentNotFoundException` |
| `ListHolderDocuments` | holderId | Porteur existant | Liste triée du plus ancien au plus récent |
| `ConsultDocument` | id | Lecture S3, déchiffrement, **recalcul du hash et comparaison** avec `file_hash` | Contenu en clair ; `DocumentIntegrityException` si divergence ; audit `document_consulted` |
| `RegisterIdentityDocument` | type, pays, numéro, expiration | Non expirée, unicité (type, pays, numéro) | Pièce ou `IdentityDocumentAlreadyExistsException` |
| `CreateStorageSpace`, `GetStorageSpace`, `ListStorageSpaces`, `ChangeStorageSpaceStatus` | code, nom, statut | Code unique | Espace ou exceptions dédiées |
| `ListAuditEvents` | subjectId optionnel, limit (défaut 100) | limit ramené dans l'intervalle 1..1000 | Événements, plus récents d'abord |
| `CheckSystemStatus` | | | `ok`, version, environnement |

---

## 5. Chiffrement et intégrité (ADR 0002)

Implémenté dans `Infrastructure/Gateway/SodiumCipherGateway` avec l'extension PHP Sodium.

### 5.1 Au dépôt

```text
plaintext ──sha256──► file_hash (stocké en base)

dataKey  = sodium_crypto_secretbox_keygen()               # 32 octets, unique au document
ciphertext = fileNonce ‖ secretbox(plaintext, fileNonce, dataKey)        → S3 : documents/<id>
wrappedKey = keyNonce  ‖ secretbox(dataKey,   keyNonce,  MASTER_KEY)     → base : wrapped_data_key
sodium_memzero(dataKey)
```

`secretbox` est XSalsa20-Poly1305 : chiffrement authentifié. Tout octet modifié dans le ciphertext fait échouer le déchiffrement avant même la vérification du hash.

### 5.2 À la consultation

1. Lecture de `documents/<id>` dans S3.
2. Ouverture de `wrapped_data_key` avec la clé maître → `dataKey`.
3. Ouverture du ciphertext avec `dataKey` → `plaintext`.
4. `sha256(plaintext)` comparé à `file_hash`. Différent → `DocumentIntegrityException` (HTTP 409), rien n'est renvoyé.
5. Effacement de `dataKey` en mémoire.

### 5.3 À l'anonymisation (crypto-shredding)

L'objet S3 est supprimé, puis la ligne `document` (et donc `wrapped_data_key`) est supprimée. Sans la clé de données, une éventuelle copie résiduelle du ciphertext est définitivement illisible.

### 5.4 La clé maître

Variable `DOCUMENT_MASTER_KEY` : 32 octets aléatoires encodés en base64. Obligatoire en production (`compose.prod.yaml` refuse de démarrer sans elle). Sa perte rend **tous** les documents irrécupérables ; sa rotation n'est pas encore outillée (point ouvert 8.8).

---

## 6. Authentification et autorisation (ADR 0013)

### 6.1 Clés d'API

- Format : `cs_` + 43 caractères base64url (32 octets aléatoires). Le préfixe permet aux scanners de secrets de les reconnaître.
- Stockage : uniquement `sha256(clé)`. La clé en clair n'est affichée qu'une fois, par `app:api-client:create`.
- Transport : `Authorization: Bearer cs_...` (RFC 6750).
- Vérification : `ApiKeyTokenHandler` cherche le hash, puis compare en temps constant (`hash_equals`). Clé absente, inconnue ou révoquée → 401 avec `WWW-Authenticate: Bearer realm="Compliance Store API"`, même message dans les trois cas.
- Révocation : `app:api-client:revoke <nom>` renseigne `revoked_at` ; effet immédiat, l'API étant sans session.

### 6.2 Rôles

```text
ROLE_ADMIN  >  ROLE_OPERATOR  >  ROLE_READER
```

Chaque opération API Platform déclare son rôle minimal dans son attribut `security`. `config/packages/security.yaml` ajoute un filet : tout `/api` exige `ROLE_READER`, sauf `/api/docs`, `/api/contexts` et `/api/v1/health`. Rôle insuffisant → 403.

### 6.3 Firewall

Un seul firewall `api` (`^/api`), `stateless: true`, authentificateur `access_token`, fournisseur d'utilisateurs Doctrine sur `ApiClient` (propriété `name`). Le profiler et les assets sont hors sécurité en dev.

---

## 7. Référence de l'API (`/api/v1`)

Format d'erreur : RFC 9457, `application/problem+json`. Format nominal : JSON-LD (`application/ld+json`) ou JSON selon `Accept`.

| Méthode et chemin | Rôle | Corps / paramètres | Succès | Erreurs spécifiques |
|-------------------|------|--------------------|--------|---------------------|
| `GET /health` | public | | 200 `{status, version, environment}` | |
| `POST /holders` | operator | `{holderName, birthDate}` | 201 | 409 collision d'identifiant (théorique), 422 validation |
| `GET /holders/{id}` | reader | | 200 | 404 inconnu ou id mal formé |
| `POST /holders/{id}/erasure-request` | admin | aucun corps | 200 | 404, 409 statut ≠ active |
| `POST /holders/{id}/anonymization` | admin | aucun corps | 200 | 404, 409 statut ≠ erasure_requested |
| `POST /documents` | operator | multipart : `file`, `holderId` | 201 | 413 > 16 MiB (Caddy), 422 taille > `DOCUMENT_MAX_SIZE`, type MIME non accepté, porteur inconnu ou non actif |
| `GET /documents/{id}` | reader | | 200 `{id, holderId, fileHash, createdAt}` | 404 |
| `GET /holders/{holderId}/documents` | reader | | 200 liste | 404 porteur inconnu |
| `GET /documents/{id}/content` | operator | `Accept: application/octet-stream` | 200 binaire, en-têtes `X-Document-Hash`, `Content-Disposition: attachment`, `Cache-Control: no-store` | 404, 406 mauvais Accept, 409 intégrité, 502 stockage injoignable |
| `POST /identity-documents` | operator | `{type, country, number, expiresAt}` | 201 | 409 doublon, 422 expirée ou invalide |
| `GET /storage-spaces`, `GET /storage-spaces/{id}` | reader | | 200 | 404 |
| `POST /storage-spaces` | admin | `{code, name}` | 201 | 409 code existant |
| `PATCH /storage-spaces/{id}` | admin | `{status}` | 200 | 404, 422 |
| `GET /audit-events` | admin | `?subjectId=&limit=` | 200 liste (limit borné à 1..1000, 100 par défaut) | |

Toute route : 401 sans clé valide, 403 rôle insuffisant.

Les fichiers `tests/http/**/*.http` donnent un exemple exécutable de chaque appel ; voir [tests/http/README.md](../../tests/http/README.md).

---

## 8. Limites de dépôt, défense en profondeur

| Niveau | Mécanisme | Seuil | Réponse |
|--------|-----------|-------|---------|
| Caddy | Matcher CEL sur `Content-Length` + `request_body max_size` | 16 MiB | 413 avant que PHP ne lise le corps |
| PHP | `upload_max_filesize`, `post_max_size` (`frankenphp/conf.d/10-app.ini`, copié dans l'image) | 12 M / 16 M | Filet de sécurité |
| Application | `Assert\File(maxSize, mimeTypes)` avec détection MIME par le contenu (`symfony/mime`) | `DOCUMENT_MAX_SIZE` (10M), `DOCUMENT_MIME_TYPES` | 422 avec violation sur `file` |

Un fichier texte renommé en `.png` est refusé : c'est le contenu qui est examiné, pas l'extension ni l'en-tête `Content-Type` fourni par le client.

---

## 9. Journal d'audit

`SecurityAwareAuditTrail` implémente `AuditTrailInterface` : il lit l'utilisateur courant via `Symfony\Bundle\SecurityBundle\Security`, prend son identifiant (le nom du client d'API) ou `system`, génère un UUID v7 et persiste un `AuditEvent`. Les use cases l'appellent **après** la réussite de l'opération métier, dans la même requête.

Limites connues : pas de rétention ni de purge, pas de signature des lignes (l'inviolabilité repose sur l'absence d'API de modification et sur les droits de la base), pas d'export.

---

## 10. Configuration

| Variable | Rôle | Dev | Prod |
|----------|------|-----|------|
| `DOCUMENT_MASTER_KEY` | Clé maître base64 (32 octets) | Valeur de test dans `compose.override.yaml` | **Obligatoire**, secret |
| `DOCUMENT_MAX_SIZE` | Taille max acceptée par l'application | `10M` (`300k` en test) | Selon besoin, < 12M |
| `DOCUMENT_MIME_TYPES` | Types acceptés, séparés par des virgules | `application/pdf,image/png,image/jpeg` | |
| `AWS_S3_ENDPOINT`, `AWS_S3_BUCKET` | Stockage objet | MinIO interne, `compliance-store-documents` | Endpoint S3 réel |
| `AWS_S3_ACCESS_KEY`, `AWS_S3_SECRET_KEY` | Identifiants S3 | `minioadmin` | **Obligatoires**, secrets |
| `DATABASE_URL`, `POSTGRES_PASSWORD` | PostgreSQL | Compose | **Mot de passe obligatoire** |
| `SERVER_NAME`, `HTTP_PORT`, `HTTPS_PORT` | Hôtes Caddy et ports publiés | `compliance-store.loc, localhost` | Domaine réel (TLS automatique) |
| `APP_SECRET`, `APP_ENV` | Symfony | `dev` | `prod` |
| `CORS_ALLOW_ORIGIN` | Origines navigateur autorisées | localhost | Domaines des front-ends |

Génération d'une clé maître : `php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'`.

Le Makefile lit `.env.local` (non versionné) avant d'appeler Docker Compose ; un `docker compose up` direct ne le fait pas. Exemple d'usage : déplacer le port PostgreSQL publié avec `POSTGRES_PORT=5434` quand 5432 est pris sur la machine hôte.

---

## 11. Qualité et tests

`make test` enchaîne, et le hook pre-commit (`make setup-hooks`) l'impose avant chaque commit :

1. **Deptrac** : 0 violation entre couches (Domain ← Application ← Infrastructure).
2. **PHPStan niveau max** : 0 erreur, docblocks génériques exigés.
3. **Resynchronisation du schéma de test** puis **PHPUnit 13** : 159 tests, 381 assertions, deux suites.

| Suite | Contenu | Dépendances |
|-------|---------|-------------|
| `unit` (`tests/Unit`) | Entités, value objects, use cases avec doubles des ports, `ApiClient`, `AuditEvent` | Aucune |
| `api` (`tests/Api`) | Appels HTTP réels via `ApiTestCase` : authentification, porteurs, documents (dépôt, lecture, téléchargement, limites), effacement, audit, espaces, pièces, santé | PostgreSQL + MinIO du Compose |

Chaque test d'API crée un client d'API éphémère via le trait `ApiClientFixture` (`createAuthenticatedClient([ROLE_...])`) et nettoie la table en `setUp`.

La CI GitHub Actions (`.github/workflows/ci.yaml`) rejoue les mêmes étapes et ajoute Super-Linter.

---

## 12. Exploitation

### 12.1 Démarrage

```bash
make build && make up && make migrate
make sf c="app:api-client:create back-office --role=ROLE_ADMIN"   # clé affichée une seule fois
```

### 12.2 Après un changement de code

- Modification de `frankenphp/conf.d/*.ini` ou du `Caddyfile` de prod : `make build` (fichiers copiés dans l'image).
- Modification de `frankenphp/Caddyfile.dev` : monté en volume, recharger avec `frankenphp reload` dans le conteneur.
- Modification de mapping Doctrine : `make migration-diff`, relire la migration générée, `make migrate`.
- En dev, FrankenPHP surveille `src/` : les workers se rechargent, les premières requêtes qui suivent peuvent être incohérentes pendant quelques secondes.

### 12.3 Production

`compose.prod.yaml` rend obligatoires les quatre secrets (clé maître, identifiants S3, mot de passe PostgreSQL). Caddy obtient les certificats TLS automatiquement pour `SERVER_NAME`. La première clé d'administrateur se crée avec la commande console dans le conteneur. Détails dans [docker-infra/production.md](../docker-infra/production.md).

### 12.4 Sauvegardes

Trois éléments doivent être sauvegardés **ensemble** : la base PostgreSQL, le bucket S3 et la clé maître. L'un sans les autres ne permet de restaurer aucun document.

---

## 13. Points ouverts et décisions à prendre

Détaillés dans [07-audit.md](07-audit.md) (§ 7.8) et [08-corrections-prioritaires.md](08-corrections-prioritaires.md) (§ 8.8) :

- Rattacher `IdentityDocument` à un `Holder` et éventuellement à un `Document`.
- Donner un rôle réel à `StorageSpace` (choix du bucket ou du préfixe par document).
- Expiration et rotation des clés d'API (`expires_at`, commande de rotation).
- Rotation de la clé maître (rechiffrement des `wrapped_data_key`).
- Rétention et purge du journal d'audit ; export signé.
- Limitation de débit par client.
- Durée de conservation des documents et purge automatique.
- Visibilité des porteurs anonymisés en lecture (aujourd'hui : id, statut, dates).

---

## 14. Où lire ensuite

| Besoin | Document |
|--------|----------|
| Comprendre sans jargon | [09-presentation-generale.md](09-presentation-generale.md) |
| Installer et lancer | [README principal](../../README.md) |
| Pourquoi tel choix | [ADR 0001 à 0013](../architecture/adr/) |
| Ce qui a été corrigé le 24 octobre 2026 et pourquoi | [README.md](README.md) |
| Exemples d'appels | [tests/http/README.md](../../tests/http/README.md) |
| Docker, TLS, Xdebug, dépannage | [docker-infra/](../docker-infra/) |
