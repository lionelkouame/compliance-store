# Revue Clean Architecture — Compliance Store

* **Date** : 2026-11-14
* **Périmètre** : `src/` complet sur `main` (`b830eed`), configuration (`deptrac.yaml`, `api_platform.yaml`, `services.yaml`), tests et documentation d'architecture.
* **Méthode** : analyse statique (Deptrac, PHPStan niveau max), lecture du code couche par couche, et vérification **par exécution** des constats douteux (appels à l'API locale, exécution des Value Objects dans le conteneur).

---

## 1. Verdict

**La structure Clean Architecture est solide et respectée : aucune dépendance ne pointe dans le mauvais sens.** Le Domaine et l'Application sont du PHP pur, la règle est verrouillée par Deptrac en CI, et toutes les fonctionnalités suivent désormais le même modèle (Request / Response / ReadModel, DTO d'Input / Output, adaptateurs sans logique).

Les faiblesses ne sont pas dans **la direction des dépendances**, mais dans **la richesse et la rigueur du Domaine** :
* certains invariants ne sont pas réellement garantis (dates impossibles acceptées, email non validé) ;
* une partie des règles métier a glissé dans la couche Application ;
* un use case contient une valeur métier codée en dur (`StoreDocument`) ;
* le « Native Compliance Core » annoncé par l'ADR 0001 n'existe pas dans le code.

| Couche              | Note  | Résumé                                                                                  |
| ------------------- | ----- | --------------------------------------------------------------------------------------- |
| Règle de dépendance | 5/5   | Aucune violation, vérifiée par Deptrac en CI                                            |
| Domain              | 3/5   | Pur, mais invariants incomplets, styles hétérogènes, entité `StorageSpace` anémique     |
| Application         | 3,5/5 | Contrat uniforme et typé ; règles métier et valeurs en dur à remonter ou extraire       |
| Infrastructure      | 4/5   | Adaptateurs fins, mapping Doctrine XML ; un trou de validation produit un 500           |
| Tests               | 3,5/5 | Use cases et API bien couverts ; peu de tests sur les Value Objects et l'Infrastructure |

---

## 2. Indicateurs mesurés

| Contrôle                                             | Résultat                                      |
| ---------------------------------------------------- | --------------------------------------------- |
| Deptrac (`Domain: []`, `Application: [Domain]`)      | **0 violation**, 397 dépendances autorisées   |
| Imports externes (`use` ou nom qualifié) dans Domain | **0**                                         |
| Imports externes dans Application                    | **0**                                         |
| Présentation → Persistence / Storage / Gateway       | **0** (passe toujours par un use case)        |
| Présentation → Domain                                | ReadModels uniquement (aucune entité exposée) |
| PHPStan niveau max                                   | **0 erreur**                                  |
| PHPUnit                                              | **121 tests** verts                           |
| Contrôles exécutés en CI                             | PHPUnit, Deptrac, PHPStan, super-linter       |
| `\InvalidArgumentException` dans Domain/Application  | **0** (verrouillé par un test d'architecture) |

---

## 3. Points forts

1. **Domaine pur.** Aucune annotation Doctrine ni API Platform sur les entités : le mapping est en XML (`Infrastructure/Persistence/Doctrine/Mapping/`) et les Value Objects passent par des types Doctrine dédiés (ADR 0003, 0004).
2. **Ports et adaptateurs.** Les use cases ne dépendent que d'interfaces du Domaine (`Domain/Port/`) : repositories, chiffrement (`CipherGatewayInterface`), stockage (`StorageGatewayInterface`), génération et validation d'identifiants.
3. **Contrat de use case uniforme.** 12 use cases sur 12 implémentent `UseCaseInterface<Request, Response>`, et PHPStan vérifie les génériques.
4. **Aucune entité ne traverse la frontière.** Les Responses portent des ReadModels, et les DTO d'Output ne contiennent que des types simples.
5. **Adaptateurs fins.** Les processors et providers ne font que convertir (DTO → Request → UseCase → Response → DTO), sans `try/catch` (ADR 0003, 0013).
6. **Erreurs traduites une seule fois.** Les exceptions métier sont classées par catégorie dans le Domaine et mappées en HTTP dans `api_platform.yaml` (ADR 0013).
7. **Règles verrouillées automatiquement.** Deptrac, PHPStan niveau max et un test d'architecture tournent en CI.
8. **Décisions documentées.** 13 ADR, et une vue d'ensemble à jour.

---

## 4. Constats

Sévérités : **🔴 Critique** (bug visible ou règle d'architecture contournée), **🟠 Majeur** (dette qui affaiblit l'architecture), **🟡 Mineur** (cohérence, hygiène).

### 🔴 C1 — Le Domaine accepte des dates de naissance impossibles — (fait) corrigé

* **Où** : `src/Domain/ValueObject/Generic/BirthDate.php:37` (`createFromFormat('Y-m-d', …)` sans `!` ni contrôle de débordement).
* **Preuve** (exécuté) : `BirthDate::make('1990-02-30')` donne `1990-03-02`, et `BirthDate::make('1990-13-45')` donne `1991-02-14`. Sur l'API, `POST /holders` avec `birthDate: 1990-02-30` répond **201** et enregistre le 2 mars.
* **Pourquoi c'est un problème d'architecture** : un Value Object doit garantir son invariant (ADR 0004). Ici le Domaine laisse entrer une donnée fausse, et aucun verrou extérieur ne le compense : le DTO ne vérifie que la présence du champ.
* **Correction** : reprendre la technique déjà utilisée dans `RegisterIdentityDocumentUseCase::parseDate` : format `'!Y-m-d'`, puis vérifier que `$date->format('Y-m-d') === $value`.

### 🔴 C2 — `POST /holders` sans email répond 500 et expose un message interne — (fait) corrigé

* **Où** : `Resource/Holder/CreateHolderInput.php:16` porte seulement `#[Assert\Email]`, qui accepte `null` ; ensuite `State/Processor/Holder/CreateHolderProcessor.php:34` exécute `Assert::string($data->email)`.
* **Preuve** (exécuté) : `{"holderName":"Jane","birthDate":"1990-01-01"}` donne **HTTP 500** avec le détail « Expected a string. Got: NULL ».
* **Pourquoi c'est un problème d'architecture** : le contrat déclaratif du DTO (ADR 0005) est incomplet. L'assertion technique de l'adaptateur devient alors la seule barrière, et elle produit une erreur serveur au lieu d'un 422.
* **Correction** : ajouter `#[Assert\NotBlank]` sur `email`. Plus largement, chaque champ vérifié par `Assert::string()` dans un processor doit avoir un `NotBlank` (ou `NotNull`) dans son DTO.

### 🔴 C3 — `StoreDocument` rattache tous les documents au même titulaire, codé en dur — ✅ corrigé

* **Où** : `src/Application/UseCase/Document/StoreDocument/StoreDocumentUseCase.php:39`, `HolderId::make('01a17b0e-e559-7fc6-b4e1-4b96eaa3262c')`.
* **Pourquoi c'est un problème** : une donnée métier essentielle (à qui appartient le document) est figée dans la couche Application. Rien n'est fourni par la requête, et l'existence du titulaire n'est pas vérifiée. Pour un produit de conformité, chaque document doit être rattaché à son titulaire réel.
* **Correction** : ajouter `holderId` à `StoreDocumentRequest` et `StoreDocumentInput`, charger le titulaire via `HolderRepositoryInterface` et lever `HolderNotFoundException` s'il n'existe pas.

### 🟠 M1 — Le « Native Compliance Core » (ADR 0001) n'existe pas dans le code

* **Constat** : `Domain/Entity/Document.php:15` décrit un document stocké « after passing the Native Compliance Core check (ADR 0001) ». L'ADR 0003 promet aussi le masquage des données personnelles avant stockage.
* Or il n'y a ni `Domain/Service/`, ni vérificateur de conformité, ni étape de masquage dans `StoreDocumentUseCase`. La vue d'ensemble mentionnait aussi des entités `Policy` et `Rule` qui n'existent pas.
* **Risque** : la documentation garantit plus que le code. Un lecteur, auditeur compris, peut croire à une protection absente.
* **Correction** : soit implémenter le service de conformité du Domaine, appelé par `StoreDocumentUseCase`, soit marquer clairement cette garantie comme « à venir » dans l'ADR 0001, dans le commentaire de `Document` et dans la documentation.

### 🟠 M2 — Le double verrou de validation est incomplet pour l'email — (fait) corrigé

* **Où** : `src/Domain/ValueObject/Document/Email.php` ne vérifie que « non vide » et « 255 caractères maximum ».
* **Preuve** (exécuté) : `Email::make('not-an-email')` est accepté.
* **Pourquoi** : l'ADR 0005 exige que le Domaine garantisse ses invariants quel que soit le point d'entrée (CLI, message bus…). Le format n'est contrôlé que par le DTO HTTP.
* **Correction** : valider le format dans le Value Object, par exemple avec `filter_var($value, FILTER_VALIDATE_EMAIL)`, qui n'ajoute aucune dépendance.

### 🟠 M3 — Des règles métier vivent dans la couche Application

* `RegisterIdentityDocumentUseCase.php:31` et `:63` : la conversion du type de document et de la date d'expiration (avec ses règles de format) est faite dans le use case.
* `ChangeStorageSpaceStatusUseCase.php:40` : la conversion du statut se fait dans le use case, alors que `Gender::fromNullable()` montre le bon emplacement (le Domaine).
* `Domain/Entity/StorageSpace.php:40` : `withStatus()` accepte **n'importe quelle** transition (une archive peut redevenir active). C'est une entité anémique, alors que `Holder` contrôle déjà ses transitions (`InvalidHolderStatusTransitionException`).
* **Correction** : créer un Value Object `ExpiryDate`, et des fabriques `IdentityDocumentType::fromString()` et `StorageSpaceStatus::fromString()` qui lèvent `InvalidValueException`. Déplacer les règles de transition dans `StorageSpace`, sur le modèle de `Holder`.

### 🟠 M4 — Des ports du Domaine exposent des types primitifs — (fait) corrigé

* **Où** : `src/Domain/Port/Repository/HolderRepositoryInterface.php:11` et `:13`, `findById(string)` et `findByEmail(string)`.
* **Pourquoi** : l'ADR 0004 interdit les primitives dans la surface publique du Domaine, et `NaturalPersonRepositoryInterface` reçoit déjà un `NaturalPersonId`. Avec une simple chaîne, un email non validé atteint directement la Persistence (cas de `GetHolderByEmailUseCase`).
* **Correction** : `findById(HolderId)` et `findByEmail(Email)`. Le use case construit le VO, qui valide l'email au passage.

### 🟠 M5 — Écriture non atomique dans `StoreDocument`

* **Où** : `StoreDocumentUseCase.php:44` puis `:46`. Le fichier chiffré est envoyé à MinIO avant l'enregistrement en base, et chaque repository fait son propre `flush()`.
* **Risque** : si l'enregistrement en base échoue, un fichier chiffré reste orphelin dans le stockage. La couche Application n'a aucune notion de transaction.
* **Correction** : ajouter un port `TransactionInterface` (ou `UnitOfWork`) dans le Domaine et supprimer le fichier si la persistance échoue. Au minimum, documenter l'ordre choisi et sa stratégie de nettoyage.

### 🟠 M6 — Une donnée personnelle circule dans les URL

* **Où** : `Resource/Holder/HolderResource.php:30`, `GET /holders/{email}`.
* **Pourquoi** : une URL est enregistrée dans les journaux d'accès, les proxies et l'historique des navigateurs. Pour un projet de conformité RGPD, l'email ne devrait pas en faire partie. Le choix de la clé de recherche relève de la couche Présentation, mais il a un impact de conformité.
* **Correction** : rechercher par `HolderId` (`GET /holders/{id}`), ou passer l'email dans le corps d'un `POST /holders/search`.

### 🟠 M7 — Configuration d'environnement codée en dur dans un use case — (fait) corrigé

* **Où** : `src/Application/UseCase/System/CheckSystemStatusUseCase.php:19-20`, valeurs par défaut `'dev'` et `'1.0.0'`, sans aucune injection dans `services.yaml`.
* **Effet** : `/api/v1/health` annonce `environment: dev` dans **tous** les environnements, production comprise.
* **Correction** : injecter `%kernel.environment%` et la version applicative via `services.yaml` (`bind` ou `arguments`).

### 🟡 m1 — Styles hétérogènes dans le Domaine

* **Value Objects** : trois conventions coexistent. Propriété `public $value` (`Email`, `CountryCode`, `StorageSpaceCode`…), propriété privée avec `value()` (`FileHash`, `WrappedDataKey`…), et `IdentifierTrait` (`HolderId`, `DocumentId`…). Seuls certains ont une fabrique `make()`.
* **Identifiants** : `StorageSpaceId` réimplémente sa propre version (`fromString()`, `public $value`) au lieu d'utiliser `IdentifierTrait`.
* **Entités** : `StorageSpace` expose des propriétés publiques, les autres utilisent des accesseurs. `Document` est `final class` (et non `readonly`).
* **Doublons** : `Email::make()` et `HolderName::make()` répètent la vérification déjà faite par le constructeur.
* **Correction** : choisir une convention (par exemple constructeur privé, `make()`, `value()`) et l'appliquer progressivement.

### 🟡 m2 — Fichiers rangés dans le mauvais dossier

* `Email` est dans `ValueObject/Document/` mais appartient au titulaire (`Holder`).
* `IdentityDocumentType` est dans `ValueObject/Document/` alors que son agrégat est `IdentityDocument`.
* `DocumentRepository` est dans `Persistence/Doctrine/`, les autres dans `Persistence/Doctrine/Repository/`.
* `DataFixtures` est dans `src/` mais n'appartient à aucune couche Deptrac, donc n'est pas contrôlé.

### 🟡 m3 — Code mort ou inutilisé

* **Ports sans consommateur** : `DocumentIdGeneratorInterface` et `StorageSpaceIdGeneratorInterface` sont implémentés mais aucun use case ne les utilise. Deux stratégies de génération d'identifiants coexistent : `GeneratorInterface` (renvoie une chaîne) et des générateurs typés (seul `IdentityDocumentIdGeneratorInterface` sert).
* **Dossiers de ports vides** : `Domain/Port/Clock/`, `Domain/Port/Event/`, `Domain/Port/Notification/`. Le port `Clock` serait pourtant utile : les entités appellent directement `new \DateTimeImmutable('now')`, ce qui rend le temps impossible à piloter dans les tests.
* **Imports inutilisés** : `IdValidatorInterface` dans `FileHash.php:8` et `WrappedDataKey.php:8`.

### 🟡 m4 — Règles de dépendance trop larges dans l'Infrastructure

* Deptrac ne distingue pas les sous-couches de l'Infrastructure : la Présentation pourrait appeler un repository Doctrine directement sans être détectée. Ce n'est pas le cas aujourd'hui (vérifié), mais rien ne l'empêche.
* `Webmozart\Assert` et `League\Flysystem` ne sont déclarés dans aucune couche (« uncovered »), donc leur usage n'est pas contrôlé. Le commit `c21a199` (« delete webmozart ») n'a pas supprimé `Webmozart\Assert`, toujours utilisé dans 10 fichiers d'Infrastructure.
* **Correction** : déclarer des sous-couches (`Presentation`, `Persistence`, `Storage`, `Gateway`) avec la règle `Presentation → Application` uniquement, et des collecteurs pour les bibliothèques.

### 🟡 m5 — Détails de conception

* `DomainException` n'est pas abstraite (`Domain/Exception/DomainException.php:7`) : une exception qui en hériterait directement, sans catégorie, donnerait un 500 (ADR 0013).
* `UseCaseInterface::execute()` reçoit `UseCaseRequestInterface`. Le type précis n'est garanti que par PHPStan : au runtime, une Request du mauvais type provoquerait une erreur peu lisible. Un `\assert($request instanceof …)` documenterait le contrat.
* Les processors appellent `$this->validator->validate($data)` alors qu'API Platform valide déjà l'Input. Cette double validation est sans effet fonctionnel, mais c'est du bruit.
* Nommage hétérogène des ReadModels : `CreateHolderRead`, `IdentifierNaturalPerson` et `Infos*`.

### 🟡 m6 — Tests

* **Value Objects** : 5 fichiers de test pour 22 Value Objects. `BirthDate` et `Email` (constats C1 et M2) n'ont aucun test.
* **Entités** : pas de test pour `StorageSpace` ni `Document`.
* **Infrastructure** : aucun test des repositories ni des types Doctrine. Les tests API les couvrent indirectement, mais pas leurs cas limites.

---

## 5. Plan d'action recommandé

> **Avancement** : les étapes 1 à 4 sont faites (constats C1, C2, C3, M2, M4, M7), avec des tests unitaires et API qui reproduisent chaque défaut.

| Priorité | Action                                                                                              | Constats | Effort |
| -------- | --------------------------------------------------------------------------------------------------- | -------- | ------ |
| 1        | Corriger `BirthDate` et `CreateHolderInput`, avec des tests (fait)                                  | C1, C2   | S      |
| 2        | Valider le format dans `Email` ; typer `HolderRepositoryInterface` avec des VO (fait)               | M2, M4   | S      |
| 3        | Injecter environnement et version dans `CheckSystemStatusUseCase` (fait)                            | M7       | XS     |
| 4        | Rattacher le document à un titulaire réel dans `StoreDocument` (fait)                               | C3       | M      |
| 5        | Remonter les règles dans le Domaine (`ExpiryDate`, fabriques d'enum, transitions de `StorageSpace`) | M3       | M      |
| 6        | Décider : implémenter le Compliance Core, ou corriger la documentation                              | M1       | M à L  |
| 7        | Atomicité stockage + base (port de transaction et nettoyage)                                        | M5       | M      |
| 8        | Remplacer `GET /holders/{email}` par une recherche sans donnée personnelle dans l'URL               | M6       | S      |
| 9        | Sous-couches Deptrac, nettoyage du code mort, harmonisation des VO, port `Clock`                    | m1 à m5  | M      |
| 10       | Tests des Value Objects, des entités restantes et des repositories                                  | m6       | M      |

*Effort : XS < 1 h, S ≈ ½ journée, M ≈ 1 à 2 jours, L > 3 jours.*
