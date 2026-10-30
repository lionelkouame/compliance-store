# Manuel de Mise à Jour : PHP & Symfony

Ce guide décrit la procédure pour mettre à jour **PHP** et **Symfony** sur le
projet Compliance Store. Il recense d'abord les *sources de vérité* des
versions, puis détaille la procédure, la vérification et le retour arrière.

> ⚠️ Avant toute mise à jour : vérifiez que la suite est verte
> (`make phpstan`, `make test`) et travaillez sur une branche dédiée.

---

## 1. Où sont définies les versions ?

Une mise à jour cohérente impose de synchroniser **plusieurs fichiers**. Ne pas
en oublier est la principale source d'erreurs.

### PHP

| Rôle | Fichier | Clé | Valeur actuelle |
|------|---------|-----|-----------------|
| Runtime (image Docker) | `Dockerfile` | `FROM dunglas/frankenphp:1-php8.5` | branche `8.5` |
| Résolveur Composer | `composer.json` | `require.php` | `>=8.4` |
| Plateforme figée (CI/lock) | `composer.json` | `config.platform.php` | `8.4.1` |
| Pipeline CI | `.github/workflows/ci.yaml` | `php-version` | `8.4` |

> 💡 **Point d'attention actuel** : l'image Docker suit la branche `8.5` (le
> conteneur tourne donc en PHP 8.5.x), alors que `composer.json` et la CI
> ciblent encore `8.4`. C'est tolérant (`>=8.4` est satisfait par 8.5), mais la
> CI valide sur une version différente de l'exécution réelle. Aligner ces
> valeurs est recommandé (voir §2.2).

### Symfony

| Rôle | Fichier | Clé | Valeur actuelle |
|------|---------|-----|-----------------|
| Contrainte des composants | `composer.json` | `require."symfony/*"` | `8.1.*` |
| Contrainte Flex | `composer.json` | `extra.symfony.require` | `8.1.*` |

Le champ `extra.symfony.require` indique à Symfony Flex quelle version installer
pour **tous** les paquets `symfony/*`. Il doit toujours rester aligné avec les
contraintes `symfony/*` du bloc `require`.

---

## 2. Mettre à jour PHP

### 2.1 Mettre à jour le patch de la branche courante (ex. 8.5.x → 8.5.y)

Le `Dockerfile` référence le tag de branche `1-php8.5`, qui pointe
automatiquement vers le dernier patch publié par `dunglas/frankenphp`. Un
simple rebuild avec `--pull` récupère la version la plus récente.

```bash
# Reconstruit l'image en retéléchargeant la base FrankenPHP la plus récente
docker compose build --pull

# Redémarre la stack avec la nouvelle image
docker compose up -d

# Vérifie la version effective
docker compose exec php php -v
```

Aucune modification de code n'est nécessaire pour un changement de patch.

### 2.2 Monter de version mineure/majeure (ex. 8.4 → 8.5, 8.5 → 8.6)

1. **Mettre à jour le runtime** dans `Dockerfile` :

   ```dockerfile
   # Avant
   FROM dunglas/frankenphp:1-php8.5 AS frankenphp_upstream
   # Après (exemple)
   FROM dunglas/frankenphp:1-php8.6 AS frankenphp_upstream
   ```

2. **Aligner `composer.json`** :

   ```jsonc
   {
     "require": {
       "php": ">=8.6"
     },
     "config": {
       "platform": {
         "php": "8.6.0"
       }
     }
   }
   ```

   > `config.platform.php` doit refléter la version **minimale réellement
   > ciblée**, pour que Composer résolve les dépendances de façon déterministe.

3. **Aligner la CI** dans `.github/workflows/ci.yaml` :

   ```yaml
   - uses: shivammathur/setup-php@v2
     with:
       php-version: '8.6'
   ```

4. **Reconstruire et réinstaller** :

   ```bash
   docker compose build --pull --no-cache
   docker compose up -d
   docker compose exec php composer update --with-all-dependencies
   ```

   > `composer update` est nécessaire car certaines dépendances peuvent avoir
   > des contraintes liées à la version de PHP.

5. **Vérifier** (voir §4).

---

## 3. Mettre à jour Symfony

Symfony suit un cycle de releases prévisible. On monte généralement de version
mineure (ex. `8.1.*` → `8.2.*`), puis de version majeure (`8.x` → `9.0`) une
fois toutes les dépréciations résolues.

### 3.1 Montée de version mineure (ex. 8.1 → 8.2)

1. **Modifier la contrainte Flex** dans `composer.json` :

   ```jsonc
   {
     "extra": {
       "symfony": {
         "require": "8.2.*"
       }
     }
   }
   ```

2. **Mettre à jour les contraintes des composants** `symfony/*` du bloc
   `require` et `require-dev` de `8.1.*` vers `8.2.*`. Une recherche-remplacement
   de `8.1.*` → `8.2.*` sur `composer.json` suffit généralement.

3. **Lancer la mise à jour** dans le conteneur :

   ```bash
   docker compose exec php composer update "symfony/*" --with-all-dependencies
   ```

4. **Appliquer les recipes** mises à jour si proposé :

   ```bash
   docker compose exec php composer recipes:update
   ```

### 3.2 Traiter les dépréciations avant une montée majeure

Avant de passer une version majeure (ex. `8.x` → `9.0`), le code ne doit plus
émettre de dépréciations.

```bash
# Exécuter la suite et afficher le rapport de dépréciations
docker compose exec php vendor/bin/phpunit

# Lister les dépréciations détectées via le profiler en dev
docker compose exec php php bin/console debug:container --deprecations
```

Corriger chaque dépréciation, puis seulement ensuite relever la contrainte
majeure dans `composer.json` comme en §3.1.

### 3.3 Vérifier la sécurité des dépendances

```bash
docker compose exec php composer audit
```

---

## 4. Vérification post-mise-à-jour

Après toute mise à jour PHP ou Symfony, exécuter l'ensemble des contrôles :

```bash
# Version PHP effective dans le conteneur
docker compose exec php php -v

# Vider le cache applicatif
docker compose exec php php bin/console cache:clear

# Analyse statique
docker compose exec php composer phpstan

# Respect de l'architecture (couches DDD)
docker compose exec php composer deptrac

# Tests
docker compose exec php vendor/bin/phpunit

# Cohérence du schéma Doctrine (si une dépendance DBAL/ORM a bougé)
docker compose exec php php bin/console doctrine:schema:validate
```

La mise à jour est considérée réussie lorsque **tous** ces contrôles passent et
que `php -v` affiche bien la version cible.

---

## 5. Retour arrière (rollback)

Toutes les modifications étant versionnées, le retour arrière est sûr :

```bash
# Annuler les modifications de fichiers (Dockerfile, composer.*, ci.yaml)
git checkout -- Dockerfile composer.json composer.lock .github/workflows/ci.yaml

# Restaurer les dépendances verrouillées et reconstruire
docker compose build --pull
docker compose up -d
docker compose exec php composer install
```

> Conservez toujours le `composer.lock` d'origine dans l'historique Git : il
> permet de restaurer un ensemble de dépendances connu et fonctionnel.

---

## 6. Récapitulatif des fichiers à modifier

| Mise à jour | Fichiers impactés |
|-------------|-------------------|
| Patch PHP (même branche) | *(aucun — rebuild `--pull` suffit)* |
| Version PHP mineure/majeure | `Dockerfile`, `composer.json`, `.github/workflows/ci.yaml` |
| Version Symfony | `composer.json` (`require`, `require-dev`, `extra.symfony.require`) |

---

## Références

* [Template Symfony Docker (dunglas)](https://github.com/dunglas/symfony-docker)
* [Mise à jour du template](updating.md)
* [Guide de déploiement en production](production.md)
