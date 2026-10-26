# 1. Environnement de développement local

Date : 24 octobre 2026. Poste : Windows 11, Docker Desktop 29.8, Docker Compose 5.5, Git 2.55, GNU Make 4.4. PHP et Composer ne sont pas installés sur l'hôte : tout s'exécute dans le conteneur FrankenPHP, comme le prévoit le projet.

## 1.1 Ce que demande le README

Le `README.md` prévoit trois étapes : une entrée DNS locale `compliance-store.loc`, `docker compose build`, puis `docker compose up -d`. L'application est attendue sur `https://compliance-store.loc/api`.

## 1.2 Ce qui a été fait

### Entrée hosts

Ajout de la ligne suivante dans `C:\Windows\System32\drivers\etc\hosts` (nécessite une élévation UAC) :

```
127.0.0.1 compliance-store.loc
```

### Git hooks du projet

Activation des hooks partagés, équivalent de `make setup-hooks` :

```
git config core.hooksPath .githooks
```

Le hook `pre-commit` lance `make test` (Deptrac, PHPStan, base de test, PHPUnit) avant chaque commit. Il a été exécuté et validé sur chacun des commits décrits dans le document 5.

### Conflit de port PostgreSQL

Deux services Windows, `postgresql-x64-16` et `postgresql-x64-18`, écoutent déjà sur 5432 et 5433. La base Docker est donc publiée sur **5434** via un fichier `.env.local`, non versionné (il est dans `.gitignore`) :

```
# Overrides locaux (non versionne). PostgreSQL Windows 16 et 18 occupent deja 5432/5433.
POSTGRES_PORT=5434
```

**Conséquence importante :** `docker compose` seul ne lit pas `.env.local`. Seul le `Makefile` le charge (`-include .env.local` puis `export`). Il faut donc toujours démarrer la stack avec `make up` ou `make start`, jamais avec `docker compose up` directement, sinon la base tente de se publier sur 5432 et échoue avec `port is already allocated`.

### Build et premier démarrage

```
docker compose build          # image frankenphp_dev, ~2 min
make up                       # php + database + minio + minio-init
```

Au premier démarrage, l'entrypoint `frankenphp/docker-entrypoint.sh` exécute `composer install` (le dossier `vendor/` est sur le bind-mount, il persiste) puis les migrations Doctrine. Le `composer install` initial a pris environ 8 minutes : GitHub a mis un timeout de 300 s sur le clone de `phpunit/phpunit`, Composer est retombé sur l'archive dist.

## 1.3 Accès une fois la stack lancée

| Service | URL | Identifiants |
|---------|-----|--------------|
| Swagger / API Platform | http://compliance-store.loc/api et https://compliance-store.loc/api | — |
| Health check | http://compliance-store.loc/api/v1/health | — |
| MinIO console | http://localhost:9001 | `minioadmin` / `minioadmin` |
| PostgreSQL Docker | `localhost:5434`, base `app` | `app` / `!ChangeMe!` |

Le certificat HTTPS est auto-signé par Caddy (`tls internal`), à accepter une fois dans le navigateur.

## 1.4 Commandes utiles

| Commande | Effet |
|----------|-------|
| `make up` / `make down` | Démarrer / arrêter la stack |
| `make logs` | Logs en direct |
| `make sh` | Shell dans le conteneur PHP |
| `make test` | Deptrac + PHPStan + resynchronisation base de test + PHPUnit |
| `make phpstan` | Analyse statique seule |
| `make deptrac` | Vérification des couches seule |
| `make composer c="..."` | Composer dans le conteneur |
| `make sf c="..."` | `bin/console` dans le conteneur |

## 1.5 Piège Git Bash sous Windows

Dans Git Bash, un argument qui commence par `/` est réécrit en chemin Windows (`/etc/frankenphp/Caddyfile` devient `C:/Program Files/Git/etc/frankenphp/Caddyfile`). Pour passer un chemin Linux à un `docker compose exec`, préfixer la commande :

```
MSYS_NO_PATHCONV=1 docker compose exec php frankenphp validate --config /etc/frankenphp/Caddyfile
```

Les cibles du `Makefile` n'utilisent que des chemins relatifs et ne sont pas concernées.
