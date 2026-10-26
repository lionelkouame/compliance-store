# 3. Doctrine : montée de DBAL en 4.5

## 3.1 Symptôme

`make test` échouait à l'étape `test-db`, et PHPUnit rapportait 29 erreurs identiques :

```
SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "storage_space" does not exist
```

La base de test existait mais n'avait aucune table. La cible `test-db` du `Makefile` enchaîne `doctrine:database:create`, `doctrine:schema:update --force` et `cache:clear` en mode `--quiet` ; l'erreur de la deuxième commande était masquée.

## 3.2 Diagnostic

Relancée sans `--quiet` :

```
docker compose exec php bin/console -e test doctrine:schema:update --force

In GenerateSchemaEventArgs.php line 41:
  The setSchema() method requires the DBAL Schema::edit() API which is not
  available in the current DBAL version. This feature requires doctrine/dbal ^4.5 or higher.
```

Chaîne de responsabilité :

| Paquet | Version installée | Rôle |
|--------|-------------------|------|
| `symfony/doctrine-bridge` | 8.1.1 | Ses `SchemaListener` (cache DBAL, lock, messenger, sessions, remember-me) appellent `GenerateSchemaEventArgs::setSchema()` |
| `doctrine/orm` | 3.6.9 | `setSchema()` lève une exception si DBAL est inférieur à 4.5 |
| `doctrine/dbal` | 4.4.4 | Version épinglée par `composer.lock` |

Le mapping lui-même était correct (`doctrine:schema:validate` : « The mapping files are correct », 4 entités). Seule la génération du schéma échouait.

Le problème préexistait au clonage : ces trois versions n'ont pas changé dans les derniers commits. La CI (`.github/workflows/ci.yaml`) exécute la même commande `doctrine:schema:update --force` et devait donc échouer aussi.

Une branche distante `fix/pin-doctrine-orm` (16 septembre 2026) contournait le problème en épinglant `doctrine/orm` sous 3.6.8, à une époque où DBAL 4.5 n'était pas publié. `main` exige depuis `doctrine/orm ^3.6.9`, cette branche est obsolète. Le fichier `.github/dependabot.yaml` porte d'ailleurs un commentaire « Drop this entry once DBAL 4.5 is out ».

## 3.3 Correction

DBAL 4.5.0 est publié. Mise à jour ciblée du lock, sans toucher à `composer.json` : `doctrine/dbal` n'y est pas une dépendance directe, sa contrainte vient de `doctrine/orm` et de `doctrine/doctrine-bundle`, qui acceptent déjà 4.5 :

```
docker compose exec php composer update doctrine/dbal
# Upgrading doctrine/dbal (4.4.4 => 4.5.0)
```

Seul `composer.lock` change (11 lignes).

## 3.4 Vérification

```
make test-db                  # exit 0
docker compose exec -e APP_ENV=test php bin/phpunit
# Tests: 81, Assertions: 144, Failures: 1   (le dernier échec est traité dans le document 4)
```

Après cette correction, `doctrine:schema:update` et `doctrine:schema:validate` fonctionnent en environnement de test. En environnement `dev`, les migrations continuent de s'appliquer au démarrage du conteneur (8 migrations, dernière `Version20261024020529`).

## 3.5 Suite recommandée

L'exclusion `doctrine/orm >= 3.7.0` dans `.github/dependabot.yaml` du dépôt d'origine peut être retirée, comme son commentaire l'indique.
