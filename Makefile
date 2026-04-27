# ─── Nexo Digital Store — Docker Makefile ────────────────────────────────────
# Usage: make <target>
.PHONY: help up down build restart logs shell migrate seed fresh test

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-18s\033[0m %s\n",$$1,$$2}'

# ── Docker lifecycle ──────────────────────────────────────────────────────────
up: ## Start all containers (detached)
	docker compose up -d

up-dev: ## Start with Vite dev server
	docker compose --profile dev up -d

down: ## Stop all containers
	docker compose down

build: ## Build/rebuild images
	docker compose build --no-cache

restart: ## Restart app container
	docker compose restart app

logs: ## Tail app logs
	docker compose logs -f app

logs-all: ## Tail all service logs
	docker compose logs -f

# ── Laravel ────────────────────────────────────────────────────────────────────
shell: ## Enter app container bash
	docker compose exec app bash

artisan: ## Run artisan command: make artisan CMD="route:list"
	docker compose exec app php artisan $(CMD)

migrate: ## Run migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Fresh migration + seed
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec app php artisan db:seed

cache-clear: ## Clear all caches
	docker compose exec app php artisan optimize:clear

optimize: ## Optimize for production
	docker compose exec app php artisan optimize

# ── Frontend ───────────────────────────────────────────────────────────────────
npm-install: ## Install npm packages
	docker compose exec app npm install

build-assets: ## Build frontend assets
	docker compose exec app npm run build

# ── Database ───────────────────────────────────────────────────────────────────
db-shell: ## Enter MySQL shell
	docker compose exec db mysql -u nexo -psecret nexo_digital_store

db-backup: ## Backup database
	docker compose exec db mysqldump -u nexo -psecret nexo_digital_store > backup_$(shell date +%Y%m%d_%H%M%S).sql

# ── Testing ────────────────────────────────────────────────────────────────────
test: ## Run PHPUnit tests
	docker compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker compose exec app php artisan test --coverage

# ── Queue / Scheduler ──────────────────────────────────────────────────────────
queue-restart: ## Restart queue workers
	docker compose restart queue

tinker: ## Laravel Tinker REPL
	docker compose exec app php artisan tinker
