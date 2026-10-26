# 4. Code : 9 erreurs PHPStan et test en échec

## 4.1 Contexte

Les deux derniers commits du dépôt d'origine, `2bcb2c7` et `675654f` (« feat: wip », 24 octobre 2026), introduisent l'agrégat `Holder` : entité, value objects, repository, use case `CreateHolder`, resource et processor API Platform, et le rattachement d'un `Document` à un `Holder`. Le commit précédent `2eb6ec5` avait déjà ajouté `holderId` à `StoreDocumentCommand`.

`make test` enchaîne Deptrac, PHPStan niveau max (`phpstan.neon`, chemin `src/`), la resynchronisation de la base de test puis PHPUnit. Deptrac passait. PHPStan s'arrêtait sur 9 erreurs dans 5 fichiers. Un test API échouait pour la même cause que l'une d'elles.

## 4.2 Récapitulatif

Trois causes de fond expliquent tout : un port laissé vide, un repository copié sans être adapté, et des appels non mis à jour après un changement de signature.

| # | Fichier : ligne | Règle PHPStan | Cause | Correctif |
|---|-----------------|---------------|-------|-----------|
| 1 | `CreateHolderUseCase.php:23` | `method.notFound` | `GeneratorInterface` ne déclare aucune méthode, `generate()` est inconnue | Déclaration de `generate(): string` dans le port |
| 2 | `CreateHolderUseCase.php:24` | `argument.type` | Conséquence du 1 : retour `mixed` passé à `HolderId::fromString(string)` | Résolu par le même correctif |
| 3 | `BirthDate.php:41` | `argument.type` | `createFromFormat()` retourne `false` sur une date invalide, passé au constructeur | Test explicite du `false`, `InvalidArgumentException` |
| 4 | `HolderRepository.php:26` | `return.unusedType` | Repository branché sur `StorageSpace::class`, `findById()` ne peut jamais retourner un `Holder` | Repository rattaché à `Holder::class` |
| 5 | `HolderRepository.php:28` | `return.type` | Idem : `findOneBy()` retourne un `StorageSpace` là où un `Holder` est promis | Même correctif, `find($id->value)` |
| 6 | `DocumentProcessor.php:43` | `argument.missing` | `StoreDocumentCommand` exige un `holderId` depuis `2eb6ec5`, l'appelant n'a pas suivi | Lecture du champ `holderId` du formulaire multipart, validation, transmission |
| 7 | `HolderProcessor.php:13` | `missingType.generics` | `ProcessorInterface` est générique, T1 et T2 non donnés | Docblock `@implements ProcessorInterface<mixed, HolderResource>` |
| 8 | `HolderProcessor.php:36` | `argument.type` | `$input->holderName` est `?string`, la commande exige `string` | Garde `null` explicite, 422 sinon |
| 9 | `HolderProcessor.php:37` | `argument.type` | Idem pour `$input->birthDate` | Même garde |

Le test `DocumentApiTest::testItStoresADocument` échouait en 500 pour la même raison que l'erreur 6 : PHP levait `ArgumentCountError: Too few arguments` à l'exécution.

## 4.3 Erreurs 1 et 2 : un port sans méthode

`App\Domain\Port\Service\GeneratorInterface` a été créé vide, et `CreateHolderUseCase` appelle `$this->generatorUuid->generate()` dessus.

**Pourquoi PHPStan se déclenche.** PHPStan raisonne sur le type déclaré, pas sur l'implémentation injectée. `SymfonyUuidGenerator` a bien une méthode `generate()`, mais le use case ne connaît que l'interface, et l'interface ne promet rien. Comme la méthode est inconnue, son retour est `mixed`, et passer `mixed` à `HolderId::fromString(string)` donne la seconde erreur.

**Pourquoi ça marchait à l'exécution.** PHP résout l'appel dynamiquement sur l'objet concret. Le contrat n'était simplement pas garanti : une autre implémentation sans `generate()` aurait planté en production sans avertissement.

**Solution.** Le port déclare `public function generate(): string;` avec un docblock qui le positionne par rapport aux générateurs typés existants (`StorageSpaceIdGeneratorInterface`, `DocumentIdGeneratorInterface`) : il rend une chaîne brute, le use case l'enveloppe dans le bon identifiant. `CreateHolderUseCase` a été réécrit dans le style des autres use cases : `declare(strict_types=1)`, propriétés `$idGenerator` et `$holders`, et `execute()` retourne désormais le `Holder` créé, ce dont le processor a besoin pour la réponse 201.

```php
interface GeneratorInterface
{
    public function generate(): string;
}
```

## 4.4 Erreur 3 : `BirthDate::fromString` ignorait l'échec du parsing

`\DateTimeImmutable::createFromFormat('Y-m-d', $value)` retourne `\DateTimeImmutable|false`, passé tel quel au constructeur.

**Pourquoi PHPStan se déclenche.** Au niveau max, un type union contenant `false` ne peut pas aller dans un paramètre qui ne l'accepte pas. Une date mal formée envoyée par un client (`"birthDate": "string"`, valeur du fichier `tests/http/v1/Holders/post.http`) aurait produit un `TypeError`, donc un 500 au lieu d'un 422.

**Solution.** Test du retour et `InvalidArgumentException`, l'exception que les processors traduisent déjà en 422. Deux précisions : le format `!Y-m-d` remet l'heure à minuit (invariant « strictement dans le passé » déterministe), et la comparaison `$date->format('Y-m-d') !== $value` rejette les dates que PHP corrige silencieusement, comme `1990-02-30` qui deviendrait le 2 mars. Le fuseau UTC est imposé pour coller au constructeur. L'import inutilisé `IdValidatorInterface` a été retiré.

```php
$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));

if (false === $date || $date->format('Y-m-d') !== $value) {
    throw new \InvalidArgumentException(\sprintf(
        'The birth date "%s" is not a valid date in Y-m-d format.',
        $value,
    ));
}

return new self($date);
```

## 4.5 Erreurs 4 et 5 : un repository copié de `StorageSpaceRepository`

`HolderRepository` a été créé en copiant `StorageSpaceRepository`. Deux lignes n'ont pas été adaptées : `@extends ServiceEntityRepository<StorageSpace>` et `parent::__construct($registry, StorageSpace::class)`.

**Pourquoi PHPStan se déclenche.** L'extension phpstan-doctrine connaît le type générique de `ServiceEntityRepository`. Avec `<StorageSpace>`, `findOneBy()` retourne `StorageSpace|null` alors que `findById()` promet `?Holder`. PHPStan en déduit que `Holder` ne peut jamais être retourné (`return.unusedType`) et qu'un `StorageSpace` est retourné à la place (`return.type`).

**Ce que ça aurait donné à l'exécution.** Le repository aurait interrogé la table `storage_space` pour chercher un holder. `persist($holder)` aurait tout de même fonctionné, car `persist` ne dépend pas de la classe du repository. Le contrôle de doublon aurait donc toujours répondu « inconnu », silencieusement.

**Solution.** Repository rattaché à `Holder::class`, docblock `<Holder>`. La signature de `findById` a été alignée sur le reste du domaine : elle prend un `HolderId` et non une `string`, comme `StorageSpaceRepositoryInterface::get(StorageSpaceId)`. Le use case passait déjà un objet `HolderId` à un paramètre `string`, ce qui ne tenait que grâce à `__toString` et à l'absence de `strict_types`. Lecture par `$this->find($id->value)`. Imports copiés et inutilisés supprimés. Le fichier reste dans `src/Infrastructure/Persistence/` (voir 4.9).

## 4.6 Erreur 6 et test en échec : `StoreDocumentCommand` a changé de signature

Le commit `2eb6ec5` a ajouté un second paramètre obligatoire `string $holderId` à `StoreDocumentCommand`. Le test unitaire `StoreDocumentUseCaseTest` a été mis à jour dans le même commit, mais pas l'appelant réel, `DocumentProcessor`.

**Pourquoi PHPStan se déclenche.** `argument.missing` : un paramètre obligatoire du constructeur n'est pas fourni.

**Pourquoi le test échouait.** `POST /api/v1/documents` arrivait jusqu'au `new StoreDocumentCommand(content: ...)`, PHP levait `ArgumentCountError`, API Platform le convertissait en 500, et `testItStoresADocument` attendait 201. Les 80 autres tests passaient parce qu'aucun autre ne traverse ce processor.

**Solution.** Le `holderId` est lu comme champ ordinaire du formulaire multipart, à côté du fichier, selon le mécanisme déjà utilisé pour les resources en `deserialize: false` :

1. `DocumentResource` gagne une propriété `holderId` avec `#[Assert\NotBlank]` et `#[Assert\Uuid]`, et `fromEntity()` l'expose dans la réponse.
2. `DocumentProcessor` lit `$request->request->get('holderId')`, le copie dans la resource, appelle le validateur (champ absent ou mal formé donne un 422 avec le message de contrainte), puis le passe à la commande.
3. `DocumentApiTest` envoie `holderId` dans `extra.parameters`, vérifie qu'il est renvoyé ; un troisième test `testItRejectsAMissingHolderId` vérifie le 422 quand il manque. `testItRejectsAMissingFile` envoie maintenant un `holderId` pour ne tester que l'absence de fichier.

```php
$holderId = $request?->request->get('holderId');
$input->holderId = \is_string($holderId) ? $holderId : null;

$this->validator->validate($input);

if (null === $input->file || null === $input->holderId) {
    throw new UnprocessableEntityHttpException('Missing required document parameters.');
}

$document = $this->useCase->execute(new StoreDocumentCommand(
    content: $input->file->getContent(),
    holderId: $input->holderId,
));
```

Le `holderId` n'est pas vérifié contre la table `holder` : le `Document` ne porte pas de clé étrangère et sa colonne `holder_id` n'existe pas encore (voir 4.9). Le test utilise donc un UUID arbitraire, comme le test unitaire.

## 4.7 Erreurs 7 à 9 : `HolderProcessor` inachevé

Le processor était un brouillon : pas de type de retour, un `die;` après l'appel du use case, un `catch (\InvalidArgumentException)` vide, aucun contrôle des champs absents.

**Erreur 7, `missingType.generics`.** `ApiPlatform\State\ProcessorInterface<T1, T2>` est générique : T1 est le type de `$data`, T2 le type retourné. Au niveau max, PHPStan exige que la classe fixe ces types. Les autres processors le font avec `@implements ProcessorInterface<mixed, StorageSpaceResource>`.

**Erreurs 8 et 9, `argument.type`.** `HolderResource::$holderName` et `$birthDate` sont `?string` parce que la resource sert aussi de porteur de validation : un champ absent doit rester `null` pour que `#[Assert\NotBlank]` le signale. Mais `CreateHolderCommand` exige deux `string`. Le code comptait sur le fait que `$this->validator->validate($input)` lève une exception quand un champ manque. PHPStan ne connaît pas cet effet de bord : pour lui, `null` reste possible.

**Solution.** Réécriture sur le modèle exact de `CreateStorageSpaceProcessor` :

1. Docblock `@implements ProcessorInterface<mixed, HolderResource>` et type de retour `: HolderResource`.
2. Garde explicite après validation : `if (null === $input->holderName || null === $input->birthDate) throw new UnprocessableEntityHttpException(...)`. Redondante à l'exécution, mais elle rend le contrat visible pour PHPStan et protège contre un futur relâchement des contraintes.
3. Exceptions métier traduites en HTTP : `HolderAlreadyExistsException` donne 409, `InvalidArgumentException` (nom vide, date invalide ou future) donne 422. Le `die;` et le `catch` vide ont disparu.
4. Retour de `HolderResource::fromEntity($holder)` : réponse 201 avec `id`, `holderName`, `birthDate`, `status`, `createdAt`.

`HolderResource` a été complétée : un `id` marqué `#[ApiProperty(identifier: true)]` (API Platform en a besoin pour construire l'IRI de la réponse, comme sur `DocumentResource`), une contrainte `#[Assert\Date]` sur `birthDate`, les champs de sortie `status` et `createdAt`. Le commentaire obsolète « No operation is exposed yet » a été retiré.

Un test API `tests/Api/HolderApiTest.php` a été ajouté : création en 201, champs manquants en 422, quatre dates invalides en 422 (`string`, `01/01/1990`, `1990-02-30`, `2999-01-01`).

## 4.8 Validation

`make test` passe intégralement, dans le conteneur FrankenPHP (PHP 8.5.12, PHPUnit 13.3.3).

| Étape | Avant | Après |
|-------|-------|-------|
| Deptrac | 0 violation | 0 violation, 311 dépendances autorisées |
| PHPStan niveau max | 9 erreurs, 5 fichiers | 0 erreur |
| PHPUnit | 81 tests, 1 échec | 88 tests, 159 assertions, 0 échec |

Fichiers modifiés :

- `src/Domain/Port/Service/GeneratorInterface.php`
- `src/Domain/Port/Repository/HolderRepositoryInterface.php`
- `src/Domain/ValueObject/BirthDate.php`
- `src/Application/UseCase/CreateHolder/CreateHolderUseCase.php`
- `src/Infrastructure/Persistence/HolderRepository.php`
- `src/Infrastructure/Presentation/ApiPlatform/V1/Resource/HolderResource.php`
- `src/Infrastructure/Presentation/ApiPlatform/V1/Resource/DocumentResource.php`
- `src/Infrastructure/Presentation/ApiPlatform/V1/State/HolderProcessor.php`
- `src/Infrastructure/Presentation/ApiPlatform/V1/State/DocumentProcessor.php`
- `tests/Api/DocumentApiTest.php`
- `tests/Api/HolderApiTest.php` (nouveau)
- `CHANGELOG.md` (section `Fixed` sous `[Unreleased]`)

## 4.9 Points restants, hors périmètre

Ces points ne gênaient ni PHPStan ni les tests, mais relevaient du même chantier `Holder`. Ils ont tous été traités dans le document 6 (phase d'amélioration), sauf la règle de dédoublonnage qui reste une décision fonctionnelle (6.8).

- [x] (traité en 6.3) **`Document.holderId` n'est pas persisté.** L'entité porte le champ mais `Document.orm.xml` ne le mappe pas et la table `document` n'a pas de colonne `holder_id`. Un document rechargé depuis la base aurait un `holderId` non initialisé. Il faut un mapping (`type="holder_id"`) et une migration, en décidant si la colonne est `NOT NULL` pour les lignes existantes et si une clé étrangère vers `holder` est voulue.
- [x] (traité en 6.2) **`Holder.orm.xml` est incomplet.** Seuls `id`, `name` et `createdAt` sont mappés. `birthDate`, `status`, `consentCollectedAt`, `erasureRequestedAt` et `anonymizedAt` ne sont pas persistés : un `Holder` relu depuis la base aurait des propriétés readonly non initialisées. Il manque aussi un type Doctrine pour `BirthDate` et `HolderStatus`.
- [x] (traité en 6.4) **Longueur du nom.** La migration `Version20261024020529` crée `name VARCHAR(20)` alors que le mapping dit 255 et que `HolderName` accepte 255 caractères. En `dev`, un nom de plus de 20 caractères sera refusé par PostgreSQL. La base de test, créée par `schema:update`, suit le mapping et n'a pas ce problème.
- [x] (traité en 6.2) **`HolderRepository` est mal rangé** : dans `src/Infrastructure/Persistence/` alors que les autres repositories Doctrine sont dans `Persistence/Doctrine/`.
- [ ] (coquille corrigée en 6.6, règle métier ouverte en 6.8) **`HolderAlreadyExistsException` est peu utile en l'état.** L'identifiant est un UUID v7 généré à l'instant, la collision est théorique. Si l'intention est d'empêcher deux holders avec le même nom et la même date de naissance, le contrôle doit porter sur ces champs. Le message contient une coquille (« with string »).
- [x] (traité en 6.6, dossier renommé `holders/` en 7) **`tests/http/v1/Holders/post.http`** envoie `"birthDate": "string"` et un champ `createdAt` ignoré. L'appel renvoie désormais un 422 propre au lieu d'un 500 ; le fichier devrait utiliser une vraie date.
- [x] (traité en 6.6) **Fichiers sans `declare(strict_types=1)`** : `CreateHolderCommand.php`, `HolderId.php`, `HolderAlreadyExistsException.php`, `IdentifierInterface.php`, `SymfonyUuidGenerator.php`.
