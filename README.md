# 🚨 Payment Simplificado – API REST

API RESTful para transferências entre usuários, desenvolvida em **Laravel (PHP 8.2+)**, com foco em **boas práticas, arquitetura limpa e confiabilidade**.

---

## 🚀 Início Rápido (Docker – recomendado)

```bash
git clone https://github.com/pablicio/simple-payment.git
cd simple-payment
docker-compose up -d
```

➡️ Acesse: **[http://localhost:8000](http://localhost:8000)**

📖 Detalhes: [DOCKER-README.md](./DOCKER-README.md)

---

## 🧩 Instalação Sem Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

⚠️ PHP 8.3?
Execute o script de correção: [docs/FIX-DEPENDENCIES.md](./docs/FIX-DEPENDENCIES.md)

---

## 🎯 Escopo do Projeto

* Usuários **comuns** e **lojistas**
* Transferências entre usuários
* Validação de regras de negócio
* Autorização e notificação externas
* Transações atômicas (rollback)
* Testes automatizados
* CI/CD com GitHub Actions
* Docker pronto para uso

---

## 📊 Endpoints Principais

### Usuários

```
GET    /api/users
POST   /api/users
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
```

### Transferência

```
POST /api/transfer
{
  "value": 100.00,
  "payer": 1,
  "payee": 2
}
```

📖 API completa: [docs/API.md](./docs/API.md)

---

## 🏗️ Arquitetura (Resumo)

```
Routes → Controllers → Services → Models → Database
```

**Padrões**

* Service Layer
* Repository (Eloquent)
* Dependency Injection
* Transações de banco
* PSR-12

📖 Detalhes: [docs/ARQUITETURA.md](./docs/ARQUITETURA.md)

---

## 🧪 Testes

```bash
# Docker
make test

# Sem Docker
php artisan test
```

---

## 🔄 CI/CD

* Testes automáticos
* Lint e análise estática
* Pipeline de PR
* Auto-fix de dependências

📖 Guia: [docs/CI-CD.md](./docs/CI-CD.md)

---

## 🛠️ Stack

* PHP 8.2+
* Laravel 11/12
* MySQL 8
* Docker / Docker Compose
* PHPUnit
* PHPStan / PHP CS Fixer
* GitHub Actions

---

## 📚 Documentação

* 📖 [Docs gerais](./docs/README.md)
* 🎮 [API](./docs/API.md)
* 🏗️ [Arquitetura](./docs/ARQUITETURA.md)
* 🐳 [Docker](./DOCKER-README.md)

---

## 📄 Licença

Projeto desenvolvido como **desafio técnico – Payment Simplificado**.

---

**Execute `docker-compose up -d` e pronto. Simples, direto e funcional.** 🚀
