# 🚨 Payment Simplificado - API RESTful

![CI](https://github.com/seu-usuario/simple-payment/workflows/CI%20-%20Tests%20and%20Code%20Quality/badge.svg)
![Lint](https://github.com/seu-usuario/simple-payment/workflows/Lint%20Pull%20Request/badge.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)
![Laravel](https://img.shields.io/badge/laravel-11%7C12-red)
![Docker](https://img.shields.io/badge/docker-ready-blue)
![License](https://img.shields.io/badge/license-MIT-green)

## 🐳 Início Ultra-Rápido com Docker (Recomendado)

```bash
# 1. Clone o repositório
git clone <url>
cd simple-payment

# 2. Inicie tudo
docker-compose up -d

# 3. Acesse
http://localhost:8000
```

**Pronto! Tudo configurado automaticamente.** 🎉

📖 **Guia Docker:** [DOCKER-README.md](./DOCKER-README.md) | [docs/DOCKER.md](./docs/DOCKER.md)

---

## 📝 Início Tradicional (Sem Docker)

### ⚠️ Problema de Compatibilidade PHP

Se você está usando PHP 8.3, execute primeiro:

**Windows:**
```cmd
fix-dependencies.bat
```

**Linux/macOS:**
```bash
./fix-dependencies.sh
```

📖 **Detalhes:** [docs/FIX-DEPENDENCIES.md](./docs/FIX-DEPENDENCIES.md)

### Instalação Manual
```bash
# 1. Instalar dependências
composer install

# 2. Configurar
cp .env.example .env
php artisan key:generate

# 3. Configurar banco no .env

# 4. Migrations
php artisan migrate

# 5. Iniciar
php artisan serve
```

---

## 🎯 O que é este projeto?

API RESTful para gestão de transferências entre usuários, seguindo as especificações do desafio Payment Simplificado.

### Funcionalidades

- ✅ **Gestão de Usuários** (comuns e lojistas)
- ✅ **Transferências** com validações completas
- ✅ **Integração Externa** (autorização e notificação)
- ✅ **Transações Atômicas** (rollback automático)
- ✅ **Validações de Negócio** robustas
- ✅ **Testes Automatizados**
- ✅ **CI/CD** com GitHub Actions
- ✅ **Docker** pronto para uso
- ✅ **Documentação Completa**

---

## 📚 Documentação

### 📖 Guias Principais

| Documento | Descrição |
|-----------|-----------|
| **[🐳 DOCKER-README.md](./DOCKER-README.md)** | Início rápido com Docker |
| **[📚 docs/README.md](./docs/README.md)** | Índice geral da documentação |
| **[🎮 docs/API.md](./docs/API.md)** | Documentação completa da API |
| **[🏗️ docs/ARQUITETURA.md](./docs/ARQUITETURA.md)** | Arquitetura e design patterns |
| **[🚀 docs/INSTALACAO.md](./docs/INSTALACAO.md)** | Instalação detalhada |
| **[🔄 docs/CI-CD.md](./docs/CI-CD.md)** | GitHub Actions e workflows |
| **[🐳 docs/DOCKER.md](./docs/DOCKER.md)** | Docker avançado |
| **[🔧 docs/FIX-DEPENDENCIES.md](./docs/FIX-DEPENDENCIES.md)** | Correção de dependências |

---

## 🚀 Uso Rápido

### Com Docker (Recomendado)

```bash
# Iniciar
docker-compose up -d

# Ou com Make
make up

# Ver logs
make logs

# Testes
make test

# Migrations
make migrate
```

### Sem Docker

```bash
# Servidor
php artisan serve

# Testes
php artisan test

# Migrations
php artisan migrate
```

---

## 📊 Endpoints da API

### 👥 Usuários

```bash
# Listar todos
GET /api/users

# Criar usuário
POST /api/users
{
  "name": "João Silva",
  "email": "joao@email.com",
  "document": "12345678900",
  "password": "senha123",
  "type": "common",
  "balance": 1000.00
}

# Ver detalhes
GET /api/users/{id}

# Atualizar
PUT /api/users/{id}

# Deletar
DELETE /api/users/{id}
```

### 💸 Transferências

```bash
# Realizar transferência
POST /api/transfer
{
  "value": 100.00,
  "payer": 1,
  "payee": 2
}
```

**📖 Documentação completa:** [docs/API.md](./docs/API.md)

---

## 🏗️ Arquitetura

```
┌─────────────┐
│   Routes    │
└──────┬──────┘
       │
┌──────▼──────┐
│ Controllers │ ← Validação HTTP
└──────┬──────┘
       │
┌──────▼──────┐
│  Services   │ ← Lógica de Negócio
└──────┬──────┘
       │
┌──────▼──────┐
│   Models    │ ← Acesso aos Dados
└──────┬──────┘
       │
┌──────▼──────┐
│  Database   │
└─────────────┘
```

### Padrões Utilizados
- ✅ Service Layer Pattern
- ✅ Repository Pattern (Eloquent)
- ✅ Dependency Injection
- ✅ Transaction Script
- ✅ PSR-12

**📖 Detalhes:** [docs/ARQUITETURA.md](./docs/ARQUITETURA.md)

---

## 🧪 Testes

```bash
# Com Docker
make test

# Sem Docker
php artisan test

# Com cobertura
php artisan test --coverage
```

---

## 🔄 CI/CD

### Workflows Implementados

- ✅ **CI Pipeline** - Testes, linter, análise estática
- ✅ **Lint PR** - Verifica qualidade em Pull Requests
- ✅ **Deploy** - Deploy automático
- ✅ **Auto-fix** - Corrige composer.lock automaticamente

**📖 Guia completo:** [docs/CI-CD.md](./docs/CI-CD.md)

---

## 🛠️ Tecnologias

- **Framework:** Laravel 11/12
- **Linguagem:** PHP 8.2+
- **Database:** MySQL 8.0
- **Containerização:** Docker + Docker Compose
- **CI/CD:** GitHub Actions
- **Testing:** PHPUnit
- **Code Quality:** PHPStan, PHP CS Fixer, PHP_CodeSniffer

---

## 🎓 Para Avaliadores

Este projeto demonstra:

### ✨ Habilidades Técnicas
- ✅ Arquitetura limpa (Service Layer, Repository Pattern)
- ✅ Código limpo (PSR-12, PHPStan nível 5)
- ✅ Testes automatizados
- ✅ CI/CD completo
- ✅ Docker pronto para produção
- ✅ Documentação profissional

### ✨ Boas Práticas
- ✅ Transações database (atomicidade)
- ✅ Lock pessimista (concorrência)
- ✅ Validação em múltiplas camadas
- ✅ Tratamento robusto de erros
- ✅ Código manutenível e escalável

### ✨ Diferenciais
- ✅ GitHub Actions configurado
- ✅ Docker com auto-setup
- ✅ Makefile para comandos simplificados
- ✅ Documentação completa (7 guias)
- ✅ Scripts de correção automática
- ✅ Suporte a múltiplas versões PHP

---

## 📞 Precisa de Ajuda?

### Problemas Comuns

**Docker não inicia?**  
→ [docs/DOCKER.md#troubleshooting](./docs/DOCKER.md)

**Erro de dependências?**  
→ [docs/FIX-DEPENDENCIES.md](./docs/FIX-DEPENDENCIES.md)

**Dúvidas sobre a API?**  
→ [docs/API.md](./docs/API.md)

**GitHub Actions falhando?**  
→ [docs/CI-CD.md](./docs/CI-CD.md)

---

## 🎯 Comandos Úteis

### Com Make (Docker)
```bash
make help          # Ver todos os comandos
make up            # Iniciar aplicação
make down          # Parar aplicação
make logs          # Ver logs
make test          # Executar testes
make shell         # Entrar no container
make migrate       # Executar migrations
make fresh-seed    # Reset DB + seed
make clean         # Limpar tudo
```

### Sem Make
```bash
# Docker Compose
docker-compose up -d
docker-compose logs -f
docker-compose exec app bash
docker-compose exec app php artisan test

# Artisan
php artisan serve
php artisan test
php artisan migrate
```

---

## 📈 Status do Projeto

- ✅ API completa implementada
- ✅ Testes com cobertura adequada
- ✅ CI/CD funcionando
- ✅ Docker configurado
- ✅ Documentação completa
- ✅ Pronto para produção

---

## 📄 Licença

Este projeto foi desenvolvido como desafio técnico para o **Payment Simplificado**.

---

## 🚀 Quick Links

- 📚 [Documentação Completa](./docs/README.md)
- 🐳 [Guia Docker](./DOCKER-README.md)
- 🎮 [API Reference](./docs/API.md)
- 🏗️ [Arquitetura](./docs/ARQUITETURA.md)
- 🔄 [CI/CD](./docs/CI-CD.md)

---

**Desenvolvido com ❤️ seguindo as melhores práticas de desenvolvimento**

**Execute `docker-compose up -d` e comece a usar! 🚀**
