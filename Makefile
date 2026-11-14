# Load .env.local if it exists (local overrides, gitignored — useful for port conflicts in WSL, etc.)
-include .env.local
export

# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

# PostgreSQL (lecture des credentials depuis l'environnement, défauts alignés sur compose.yaml)
POSTGRES_USER ?= app
POSTGRES_DB   ?= app
# Les flags (-e, -T) doivent précéder le nom du service pour `docker compose exec`.
# PSQL      : commandes non-interactives (-c, dumps) → -T désactive le pseudo-TTY,
#             sinon docker réclame un TTY quand la sortie est redirigée.
# PSQL_IT   : shell psql interactif (TTY conservé).
DB_RUN     = $(DOCKER_COMP) exec -e PGPASSWORD=$${POSTGRES_PASSWORD:-!ChangeMe!}
PSQL       = $(DB_RUN) -T database psql -U $(POSTGRES_USER) -d $(POSTGRES_DB)
PSQL_IT    = $(DB_RUN) database psql -U $(POSTGRES_USER) -d $(POSTGRES_DB)

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build refresh up start down logs sh bash test test-db deptrac phpstan composer vendor sf cc migration-diff migration-generate migrate migration-up migration-down setup-dns setup-hooks website-build db db-query db-tables db-describe db-dump db-size

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— DNS & Setup 🌐 ———————————————————————————————————————————————————————————
setup-dns: ## Ajoute compliance-store.loc au fichier /etc/hosts (nécessite sudo)
	@if grep -q "compliance-store.loc" /etc/hosts 2>/dev/null; then \
		echo "✅ compliance-store.loc est déjà présent dans /etc/hosts"; \
	else \
		echo "Ajout de compliance-store.loc dans /etc/hosts..."; \
		echo "127.0.0.1 compliance-store.loc" | sudo tee -a /etc/hosts > /dev/null && echo "✅ compliance-store.loc a été ajouté avec succès !"; \
	fi

setup-hooks: ## Active les git hooks partagés (.githooks) pour le projet
	@git config core.hooksPath .githooks && echo "✅ Git hooks configurés (.githooks)"


## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images (uses cache, fast — daily use)
	@$(DOCKER_COMP) build

# --pull refetches base images even if present locally, --no-cache reruns every layer from scratch.
# Slower than `build`, but use it when you suspect a stale cache/base image (e.g. before a deploy, in CI).
refresh: ## Rebuilds the Docker images from scratch, ignoring cache and pulling latest base images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
test: deptrac phpstan test-db
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

# Aligne la base et le cache de test sur le code courant, comme le fait la CI avant PHPUnit.
# Sans ça, un schéma laissé par une branche précédente fait échouer des tests sans rapport
# avec le code en cours — le gate devient un oracle peu fiable.
test-db: ## Resynchronise la base et le cache de l'environnement de test
	@$(PHP_CONT) bin/console -e test doctrine:database:create --if-not-exists --quiet
	@$(PHP_CONT) bin/console -e test doctrine:schema:update --force --quiet
	@$(PHP_CONT) bin/console -e test cache:clear --quiet

## —— Qualité 🧪 ————————————————————————————————————————————————————————————————
deptrac: ## Vérifie le respect des couches Clean Architecture (Domain/Application/Infrastructure)
	@$(PHP_CONT) vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-interaction

phpstan: ## Analyse statique du code avec PHPStan (niveau max)
	@$(PHP_CONT) vendor/bin/phpstan analyse --memory-limit=512M

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

## —— Doctrine 🗄️ ——————————————————————————————————————————————————————————————
migration-diff: ## Génère une migration à partir des différences entre les entités et la base
	@$(SYMFONY) doctrine:migrations:diff

migration-generate: ## Génère un fichier de migration vide
	@$(SYMFONY) doctrine:migrations:generate

migrate: ## Applique les migrations en attente
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction

migration-up: ## Exécute le up d'une migration, example: make migration-up v=Version20261024020529
	@test -n "$(v)" || (echo "Usage: make migration-up v=Version20261024020529" && exit 1)
	@$(SYMFONY) doctrine:migrations:execute "DoctrineMigrations\\$(v)" --up --no-interaction

migration-down: ## Exécute le down d'une migration, example: make migration-down v=Version20261024020529
	@test -n "$(v)" || (echo "Usage: make migration-down v=Version20261024020529" && exit 1)
	@$(SYMFONY) doctrine:migrations:execute "DoctrineMigrations\\$(v)" --down --no-interaction

migrate-all: migration-diff migration-generate migrate

## —— Base de données 🐘 ———————————————————————————————————————————————————————
db: ## Ouvre un shell psql interactif sur la base PostgreSQL
	@$(PSQL_IT)

db-query: ## Exécute une requête SQL, example: make db-query q="SELECT * FROM document LIMIT 5;"
	@test -n "$(q)" || (echo 'Usage: make db-query q="SELECT * FROM document LIMIT 5;"' && exit 1)
	@$(PSQL) -c "$(q)"

db-tables: ## Liste toutes les tables de la base
	@$(PSQL) -c "\dt"

db-describe: ## Décrit la structure d'une table, example: make db-describe t=document
	@test -n "$(t)" || (echo "Usage: make db-describe t=document" && exit 1)
	@$(PSQL) -c "\d $(t)"

db-size: ## Affiche la taille de la base de données
	@$(PSQL) -c "SELECT pg_size_pretty(pg_database_size('$(POSTGRES_DB)')) AS size;"

db-dump: ## Exporte la base dans var/dump_<timestamp>.sql
	@mkdir -p var
	@$(eval DUMP_FILE := var/dump_$(shell date +%Y%m%d_%H%M%S).sql)
	@$(DB_RUN) -T database pg_dump -U $(POSTGRES_USER) -d $(POSTGRES_DB) > $(DUMP_FILE)
	@echo "✅ Dump créé : $(DUMP_FILE)"

## —— Website 📖 ———————————————————————————————————————————————————————————————
website-build: ## Build the Docusaurus website (website/)
	@cd website && npm ci && npm run build
