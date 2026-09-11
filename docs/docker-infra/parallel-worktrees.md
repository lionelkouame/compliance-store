# Faire tourner plusieurs worktrees en parallèle

Un `git worktree` permet de travailler sur plusieurs branches en même temps. Deux instances
de la stack lancées simultanément entrent en revanche en conflit sur les **ports publiés**.

## Ce qui est déjà isolé

`COMPOSE_PROJECT_NAME` n'est pas défini : Compose le dérive du **nom du dossier**. Deux
worktrees ont donc automatiquement des conteneurs, réseaux et volumes distincts. Il n'y a
rien à faire de ce côté.

## Ce qu'il faut décaler

Tous les ports publiés sont paramétrables, avec les valeurs historiques par défaut :

| Variable | Défaut | Service |
| :--- | :---: | :--- |
| `HTTP_PORT` | 80 | FrankenPHP |
| `HTTPS_PORT` | 443 | FrankenPHP |
| `HTTP3_PORT` | 443 | FrankenPHP (UDP) |
| `POSTGRES_PORT` | 5432 | PostgreSQL |
| `MINIO_PORT` | 9000 | MinIO — API S3 |
| `MINIO_CONSOLE_PORT` | 9001 | MinIO — console web |

Sans variable définie, le comportement est **strictement inchangé**.

## Mise en place

Le `Makefile` charge `.env.local` s'il existe (fichier non versionné). Dans le worktree
secondaire :

```bash
cat > .env.local <<'ENV'
HTTP_PORT=8081
HTTPS_PORT=8443
HTTP3_PORT=8443
POSTGRES_PORT=5442
MINIO_PORT=9010
MINIO_CONSOLE_PORT=9011
ENV

make setup-hooks
make start
```

Vérifier le résultat avant de démarrer :

```bash
docker compose config | grep -A2 published
```

## Ce qui ne change pas

Les services communiquent **entre eux** par le réseau interne Docker (`database:5432`,
`minio:9000`). Décaler un port publié n'affecte que l'accès depuis l'hôte : aucune
configuration applicative n'est à modifier.
