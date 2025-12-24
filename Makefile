.PHONY: help build up down restart logs shell test migrate fresh seed clean prepare

# Cores para output
BLUE=\033[0;34m
GREEN=\033[0;32m
RED=\033[0;31m
YELLOW=\033[0;33m
NC=\033[0m # No Color

help: ## Mostrar ajuda
	@echo "$(BLUE)PicPay Simplificado - Comandos Docker$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

prepare: ## Preparar projeto para Docker (remove composer.lock e vendor)
	@echo "$(YELLOW)🔧 Preparando projeto para Docker...$(NC)"
	@rm -f composer.lock
	@rm -rf vendor
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@echo "$(GREEN)✅ Projeto preparado! Execute: make build$(NC)"

build: ## Construir containers
	@echo "$(BLUE)Construindo containers...$(NC)"
	docker-compose build --no-cache

up: ## Iniciar containers
	@echo "$(BLUE)Iniciando containers...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✅ Aplicação rodando em http://localhost:8000$(NC)"

down: ## Parar containers
	@echo "$(BLUE)Parando containers...$(NC)"
	docker-compose down

restart: down up ## Reiniciar containers

logs: ## Ver logs
	docker-compose logs -f

shell: ## Acessar shell do container app
	docker-compose exec app bash

test: ## Executar testes
	@echo "$(BLUE)Executando testes...$(NC)"
	docker-compose exec app php artisan test

migrate: ## Executar migrations
	@echo "$(BLUE)Executando migrations...$(NC)"
	docker-compose exec app php artisan migrate

rollback: ## Rollback da última migration
	docker-compose exec app php artisan migrate:rollback

fresh: ## Resetar banco e executar migrations
	@echo "$(RED)⚠️  Isso vai APAGAR todos os dados!$(NC)"
	docker-compose exec app php artisan migrate:fresh

seed: ## Popular banco com dados de teste
	docker-compose exec app php artisan db:seed

fresh-seed: fresh seed ## Fresh + Seed

install: ## Instalar dependências
	docker-compose exec app composer install

update: ## Atualizar dependências
	docker-compose exec app composer update

cache-clear: ## Limpar cache
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

optimize: ## Otimizar aplicação
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

clean: ## Limpar tudo (containers, volumes, cache)
	@echo "$(RED)⚠️  Isso vai REMOVER todos os dados!$(NC)"
	docker-compose down -v
	docker system prune -f

ps: ## Ver status dos containers
	docker-compose ps

mysql: ## Conectar ao MySQL
	docker-compose exec db mysql -uroot -psecret payment_simplificado

backup: ## Backup do banco
	@echo "$(BLUE)Criando backup...$(NC)"
	docker-compose exec db mysqldump -uroot -psecret payment_simplificado > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Backup criado!$(NC)"

restore: ## Restaurar backup (use: make restore FILE=backup.sql)
	@echo "$(BLUE)Restaurando backup...$(NC)"
	docker-compose exec -T db mysql -uroot -psecret payment_simplificado < $(FILE)
	@echo "$(GREEN)✅ Backup restaurado!$(NC)"

tinker: ## Abrir Laravel Tinker
	docker-compose exec app php artisan tinker

setup: prepare build up ## Setup completo (primeira vez)
	@echo "$(GREEN)✅ Setup completo! Aguarde a aplicação inicializar...$(NC)"
	@echo "$(YELLOW)💡 Execute 'make logs' para ver o progresso$(NC)"

dev: up logs ## Modo desenvolvimento (up + logs)

fix: ## Corrigir problemas comuns (rebuild completo)
	@echo "$(YELLOW)🔧 Corrigindo problemas...$(NC)"
	make down
	@rm -f composer.lock
	@rm -rf vendor
	make build
	make up
	@echo "$(GREEN)✅ Projeto reconstruído!$(NC)"
