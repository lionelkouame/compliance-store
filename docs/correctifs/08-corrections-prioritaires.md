# 8. Corrections prioritaires : authentification, limites de dépôt, RGPD, intégrité, périmètre

Phase menée le 24 octobre 2026 à la suite de l'audit (document 7). Elle traite, dans l'ordre de priorité annoncé, les cinq faiblesses relevées dans l'avis rendu sur l'application : absence d'authentification, dépôts non bornés, parcours RGPD non persistable, objet orphelin dans le stockage en cas d'échec de persistance, périmètre métier trop mince. Les cinq points de priorité haute de la section 7.8 sont traités ici (1 à 5).

Tout est validé par `make test` à chaque commit (hook pre-commit) : Deptrac 0 violation, PHPStan 0 erreur, PHPUnit **159 tests et 381 assertions** (107 tests et 200 assertions à la fin de l'audit).

## 8.1 Vue d'ensemble

| Priorité | Faiblesse | Réponse | Commit |
|----------|-----------|---------|--------|
| 1 | N'importe qui pouvait appeler l'API | Clés d'API hachées, trois rôles hiérarchisés, 401/403 | `5c2a90b` |
| 2 | Aucune limite de taille ni de type sur les dépôts | Limites configurables, contrôle du type par le contenu, 413 au niveau de Caddy | `78bf646` |
| 3 | Effacement RGPD inatteignable, anonymisation incomplète | Deux endpoints administrateur, suppression cryptographique des documents, date de naissance effacée | `6487af0` |
| 4 | Objet chiffré orphelin si l'INSERT échoue | Compensation : suppression du ciphertext, erreur d'origine conservée | `6487af0` |
| 5 | Pas de lecture, pas de liste, pas de journal d'audit | Métadonnées, liste par porteur, téléchargement déchiffré avec contrôle d'intégrité, journal d'audit | `5237134` |

## 8.2 Authentification et autorisation (priorité 1)

### Modèle

Les appelants sont des machines, pas des personnes : le modèle retenu est la **clé d'API porteuse** (RFC 6750, `Authorization: Bearer cs_...`), sans mot de passe ni session. Il est décrit dans l'[ADR 0013](../architecture/adr/0013-api-key-authentication.md).

| Élément | Implémentation |
|---------|----------------|
| Entité | `App\Infrastructure\Security\ApiClient` (table `api_client`), mapping XML dans `src/Infrastructure/Security/Mapping/`. Elle n'entre pas dans le domaine : le métier ignore qui appelle. |
| Clé | 32 octets aléatoires en base64 URL-safe, préfixe `cs_` pour être reconnaissable dans les journaux et les scanners de secrets. Seul le **SHA-256** est stocké ; la clé claire est affichée une fois à la création. |
| Authentificateur | Firewall `api` sans état, `access_token` avec `ApiKeyTokenHandler` (recherche par hachage, comparaison en temps constant). Clé absente, inconnue ou révoquée : 401 avec `WWW-Authenticate: Bearer realm="Compliance Store API"` (`ApiKeyEntryPoint`). |
| Rôles | `ROLE_ADMIN` ⊃ `ROLE_OPERATOR` ⊃ `ROLE_READER` (`role_hierarchy`). Chaque opération API Platform déclare son rôle avec `security:` ; `access_control` ajoute un filet par chemin. |
| Public | `GET /v1/health`, `/api/docs*`, `/api/contexts/*`. |
| Commandes | `app:api-client:create <nom> --role=ROLE_ADMIN [--role=...]`, `app:api-client:revoke <nom>`. |
| Migration | `Version20261024093000`. |

### Matrice des droits

| Opération | Rôle minimal |
|-----------|--------------|
| `GET /v1/health`, documentation OpenAPI | aucun |
| `GET /v1/holders/{id}`, `GET /v1/holders/{id}/documents`, `GET /v1/documents/{id}`, `GET /v1/storage-spaces`, `GET /v1/storage-spaces/{id}` | `ROLE_READER` |
| `POST /v1/holders`, `POST /v1/documents`, `GET /v1/documents/{id}/content`, `POST /v1/identity-documents` | `ROLE_OPERATOR` |
| `POST /v1/storage-spaces`, `PATCH /v1/storage-spaces/{id}`, `POST /v1/holders/{id}/erasure-request`, `POST /v1/holders/{id}/anonymization`, `GET /v1/audit-events` | `ROLE_ADMIN` |

### Dépendances ajoutées

`symfony/security-bundle` 8.1.9 (et ses composants `security-core`, `security-http`, `security-csrf`, `password-hasher`), `symfony/expression-language` 8.1.9 (exigé par l'attribut `security` d'API Platform, l'absence provoquait une 500). La recette Flex a créé `config/packages/security.yaml`, entièrement réécrit, et `config/routes/security.yaml` (route de déconnexion), supprimé car il n'y a pas de session.

### Tests

`ApiClientFixture` (trait) crée un client jetable avec le rôle voulu et renvoie un client HTTP déjà muni de l'en-tête. Tous les tests API existants l'utilisent. `AuthenticationApiTest` couvre : santé et documentation sans clé, 401 sans clé, clé inconnue, clé révoquée, 403 lecteur sur une écriture, 403 opérateur sur un espace de stockage, administrateur partout. `ApiClientTest` (unitaire) vérifie le préfixe, l'absence de la clé claire dans toute sérialisation, l'idempotence de la révocation et les rejets.

### Points d'attention

* `symfony/security-bundle` ne pose pas la question « qui crée la première clé ? » : c'est une opération d'exploitation, faite avec la commande console dans le conteneur. Le `README.md` l'explique.
* Les clés ne tournent pas automatiquement et n'expirent pas : la révocation est manuelle. Voir 8.8.

## 8.3 Limites de dépôt (priorité 2)

| Couche | Réglage | Fichier |
|--------|---------|---------|
| Application | `DOCUMENT_MAX_SIZE=10M`, `DOCUMENT_MIME_TYPES=application/pdf,image/png,image/jpeg` ; contrainte `Assert\File` construite à l'exécution dans `DocumentProcessor`, violation `file` en 422 | `.env`, `config/services.yaml`, `DocumentProcessor` |
| PHP | `upload_max_filesize = 12M`, `post_max_size = 16M` | `frankenphp/conf.d/10-app.ini` (copié dans l'image : `make build` nécessaire) |
| Caddy | `Content-Length > 16 MiB` ⇒ 413 avant d'atteindre PHP ; `request_body max_size 16MiB` en filet | `frankenphp/Caddyfile`, `frankenphp/Caddyfile.dev` |

Le type MIME est **déduit du contenu** (`symfony/mime`, ajouté pour l'occasion), jamais pris dans le nom ou l'en-tête envoyés par le client : un fichier texte renommé `passport.png` est refusé.

**Pourquoi un contrôle dans Caddy.** Quand `Content-Length` dépasse `post_max_size`, PHP n'émet qu'un avertissement et continue avec un corps vide. Sous le runtime worker de FrankenPHP, cet avertissement devient une `ErrorException` non rattrapée : la requête de 17 Mo renvoyait une **page d'erreur HTML en 200** et redémarrait le worker. Le matcher d'expression sur `Content-Length` règle le problème en amont ; une requête sans `Content-Length` passe et reste bornée par `request_body`.

**Mercure en production.** En validant le Caddyfile de production avec la valeur réelle de `SERVER_NAME` (`compliance-store.loc, localhost, php:80`), Caddy refusait de démarrer : plusieurs hôtes donnent plusieurs serveurs qui partagent le bloc `mercure`, et Mercure ≥ 0.21 n'admet qu'un seul hub sans nom. Le hub est maintenant nommé (`name default`) comme en dev. La validation de l'audit (7.3) avait été faite avec un seul hôte et n'avait pas vu ce cas.

Tests : `DocumentApiTest` gagne un cas « contenu texte déguisé en PNG » et un cas « PNG valide au-delà de la limite » (`DOCUMENT_MAX_SIZE=300k` dans `.env.test` pour ne pas manipuler 10 Mo).

## 8.4 Parcours RGPD persistable (priorité 3)

### Domaine

* `Holder::anonymize()` ne prend plus de nom en paramètre : le nom devient `Holder::ANONYMIZED_NAME` (`anonymized`) et **la date de naissance passe à `null`**. Identifiant et horodatages sont conservés (redevabilité). `birthDate()` retourne `?BirthDate`, colonne `birth_date` rendue nullable (`Version20261024100000`).
* `HolderRepositoryInterface::update()` : mise à jour DQL sur le modèle de `StorageSpaceRepository`, `HolderNotFoundException` si aucune ligne, instance périmée détachée.
* `DocumentRepositoryInterface` : `findById()`, `findByHolder()`, `remove()`. `StorageGatewayInterface::delete()`.
* `HolderNotActiveException` : un document ne peut plus être déposé pour un porteur qui a demandé l'effacement ou qui est anonymisé (422).

### Cas d'usage

| Cas d'usage | Endpoint | Effet |
|-------------|----------|-------|
| `RequestHolderErasureUseCase` | `POST /v1/holders/{id}/erasure-request` (200) | Passe un porteur actif en `erasure_requested`. Rien n'est détruit. 409 si le porteur n'est pas actif. |
| `AnonymizeHolderUseCase` | `POST /v1/holders/{id}/anonymization` (200) | Vérifie la transition, puis pour chaque document : suppression du ciphertext dans le stockage et de la ligne (donc de la clé enveloppée), puis anonymisation du porteur. 409 sans demande en attente. |

**Suppression cryptographique.** La ligne `document` porte la clé de données enveloppée. La supprimer rend illisible toute copie du ciphertext qui aurait survécu (sauvegarde, réplication) : c'est le filet derrière la suppression physique dans le bucket. Si une suppression échoue, le porteur reste en `erasure_requested` et l'appel peut être rejoué.

Tests : `HolderErasureApiTest` (parcours complet avec vérification que le fichier disparaît de `var/test_storage` et la ligne de la base, double demande 409, anonymisation sans demande 409, 404, 403 opérateur, dépôt refusé après anonymisation), `RequestHolderErasureUseCaseTest`, `AnonymizeHolderUseCaseTest` (ordre des suppressions, aucune destruction si la transition est invalide, échec de stockage laisse le porteur en attente), `HolderTest` adapté.

## 8.5 Orphelin de stockage (priorité 4)

`StoreDocumentUseCase` écrit le ciphertext avant d'insérer la ligne. Si `documents->add()` lève une exception, le cas d'usage supprime désormais l'objet du stockage puis relance l'exception d'origine. Si la suppression échoue à son tour, l'exception d'origine reste celle rapportée : l'objet restant est de toute façon illisible, sa clé enveloppée n'ayant jamais été persistée. Deux tests unitaires couvrent la compensation et la priorité de l'erreur d'origine.

## 8.6 Périmètre métier (priorité 5)

### Lecture des documents

| Endpoint | Rôle | Comportement |
|----------|------|--------------|
| `GET /v1/documents/{id}` | lecteur | Métadonnées : porteur, hachage SHA-256, date. Jamais la clé enveloppée. |
| `GET /v1/holders/{holderId}/documents` | lecteur | Liste des documents du porteur, du plus ancien au plus récent ; 404 si le porteur est inconnu (et non une liste vide). |
| `GET /v1/documents/{id}/content` | opérateur | Octets déchiffrés en `application/octet-stream`, `Content-Disposition: attachment`, `Cache-Control: no-store, private`, `X-Document-Hash`. Tout autre `Accept` donne 406 (ADR 0011). |

`ConsultDocumentUseCase` recalcule le hachage du clair et le compare à celui enregistré au dépôt **avant** de rendre le contenu. L'enveloppe Sodium ne lie pas la clé au document (point 5 de la section 7.8) : cette comparaison est ce qui détecte une paire clé/ciphertext échangée. Un ciphertext altéré est rejeté par le chiffrement authentifié (502, message générique, cause en `previous`) ; un clair qui ne correspond pas au hachage donne 409 (`DocumentIntegrityException`).

Le fournisseur `GetDocumentContentProvider` retourne une `Response` Symfony, exactement comme le prescrit l'ADR 0011 (qui nommait déjà ce fournisseur et ce cas d'usage). Les réponses OpenAPI sont déclarées à la main.

### Journal d'audit

| Élément | Implémentation |
|---------|----------------|
| Entité | `AuditEvent` (domaine) : identifiant, `AuditAction`, identifiant du sujet, acteur, instant UTC. Table `audit_event` en ajout seul, index `(subject_id, occurred_at)` et `occurred_at`. Migration `Version20261024110000`. |
| Port | `AuditTrailInterface::record(AuditAction, string $subjectId)` : les cas d'usage disent ce qui s'est passé, l'adaptateur sait qui l'a fait. |
| Adaptateur | `SecurityAwareAuditTrail` : acteur = nom du client d'API authentifié, `system` hors requête. |
| Actions | `holder_registered`, `holder_erasure_requested`, `holder_anonymized`, `document_stored`, `document_consulted`. |
| Lecture | `GET /v1/audit-events?subjectId=&limit=` (administrateur), du plus récent au plus ancien, 100 par défaut, 1000 au plus. |

Tests : `DocumentReadApiTest` (métadonnées, liste ordonnée, téléchargement identique octet pour octet, 406, ciphertext altéré jamais rendu, 404, lecteur sans téléchargement), `AuditEventApiTest` (cinq actions dans l'ordre avec le bon acteur, filtre par sujet, limite, 403 opérateur), `ConsultDocumentUseCaseTest`, `ListHolderDocumentsUseCaseTest`, `AuditEventTest`.

## 8.7 Vérification

| Contrôle | Résultat |
|----------|----------|
| `make test` (hook) sur chaque commit | Deptrac 0 violation (4 dépendances vendeur non couvertes, comme avant), PHPStan 0 erreur, PHPUnit 159 tests / 381 assertions |
| `lint:container` | OK après chaque modification de configuration |
| `doctrine:migrations:migrate` puis `doctrine:schema:validate` en dev | 3 migrations appliquées, schéma en phase |
| `frankenphp validate` | `Caddyfile` et `Caddyfile.dev` valides, y compris avec `SERVER_NAME` à trois hôtes |
| Essais réels sur l'API de dev (`curl`) | 401 sans clé avec `WWW-Authenticate`, 401 clé révoquée, 200 avec clé ; 17 Mo ⇒ 413 ; texte déguisé en PNG ⇒ 422 ; demande d'effacement 200 puis 409 ; anonymisation 200, nom `anonymized`, dépôt suivant 422 ; téléchargement identique à la fixture ; journal d'audit avec l'acteur |

Les clients d'API et données créés pour ces essais ont été révoqués et supprimés.

## 8.8 Ce qui reste à décider

Les points 6 à 27 de la section 7.8 restent ouverts. Cette phase en ajoute quelques-uns :

1. **Rotation et expiration des clés d'API.** Pas de date d'expiration ni de rotation automatique ; une clé compromise se révoque à la main. Un champ `expires_at` et une commande de rotation seraient la suite logique.
2. **Limitation de débit.** Rien ne borne le nombre de requêtes d'un client ; `symfony/rate-limiter` sur le firewall `api` est la solution naturelle.
3. **Type et nom du fichier d'origine.** `Document` ne conserve ni le type MIME ni le nom déposé : le téléchargement est servi en `application/octet-stream` sous le nom `<id>.bin`. Les stocker est une décision de modèle (et une colonne de données personnelles de plus à effacer).
4. **Rétention du journal d'audit.** Le journal est en ajout seul et n'est jamais purgé. La durée de conservation est une décision de conformité, pas une décision technique.
5. **Le porteur anonymisé reste lisible.** `GET /v1/holders/{id}` renvoie l'enregistrement anonymisé (identifiant, statut, dates). C'est voulu pour la redevabilité ; une alternative serait un 410 Gone.
6. **`IdentityDocument` n'est pas rattaché à un porteur** et n'est donc pas touché par l'anonymisation (point 16 de la section 7.8, devenu plus visible).
7. **Liaison clé/document dans l'enveloppe.** Le contrôle d'intégrité par hachage compense l'absence de données additionnelles authentifiées ; lier l'identifiant du document dans l'enveloppe (AEAD) resterait préférable à terme et demande une migration des documents existants.
8. **Routes d'élément générées par API Platform** pour les ressources sans opération `Get` (`/v1/identity_documents/{id}`, `/v1/audit_events/{id}`) : elles répondent 404 et exigent une clé, mais polluent la documentation.

## 8.9 Commits

| Commit | Message |
|--------|---------|
| `5c2a90b` | `feat(security): authenticate API clients with hashed bearer API keys and a role hierarchy` |
| `78bf646` | `feat(document): enforce upload size and content-type limits end to end` |
| `6487af0` | `feat(gdpr): persist the erasure workflow with crypto-shredding, refuse orphan ciphertexts` |
| `5237134` | `feat(document): read documents back and keep an audit trail of every personal-data action` |
