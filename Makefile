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

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build refresh up start down logs sh bash test deptrac composer vendor sf cc setup-dns setup-hooks

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
test: deptrac
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

## —— Qualité 🧪 ————————————————————————————————————————————————————————————————
deptrac: ## Vérifie le respect des couches Clean Architecture (Domain/Application/Infrastructure)
	@$(PHP_CONT) vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-interaction

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
