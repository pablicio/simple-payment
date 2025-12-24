# 📚 Documentação do Simple Payment

Bem-vindo à documentação completa do sistema Simple Payment.

## 📖 Índice

### 🚀 Início Rápido
- **[Instalação](INSTALACAO.md)** - Guia completo de configuração do ambiente
- **[API](API.md)** - Referência completa da API REST

### 🏗️ Arquitetura e Design
- **[Arquitetura](ARQUITETURA.md)** - Visão geral da arquitetura do sistema
- **[Sistema de Cache](CACHE_SYSTEM.md)** - Estratégia de cache e performance
- **[Sistema de Notificações](NOTIFICATION_SYSTEM.md)** - Processamento assíncrono e resiliência

### 🔧 DevOps e Deploy
- **[Docker](DOCKER.md)** - Containerização e deploy com Docker
- **[CI/CD](CI-CD.md)** - Pipeline de integração e deploy contínuo

### 📊 Observabilidade e Monitoramento
- **[Observabilidade](OBSERVABILIDADE.md)** - Logs, métricas e rastreamento
- **[Segurança](SEGURANCA.md)** - Práticas de segurança implementadas

## 🎯 Sobre o Projeto

Sistema de transferências financeiras construído com Laravel 11, implementando todas as regras de negócio do desafio Payment Simplificado com melhorias de performance e resiliência.

### Principais Características

- ✅ **Performance**: Cache inteligente com ganhos de 87-98%
- ✅ **Resiliência**: Sistema de retry com backoff exponencial
- ✅ **Segurança**: Rate limiting, validações robustas e transações atômicas
- ✅ **Observabilidade**: Logs estruturados, métricas e rastreamento
- ✅ **Escalabilidade**: Arquitetura modular e processamento assíncrono

## 🏛️ Arquitetura

### Tipo: Monólito Modular

O sistema utiliza uma arquitetura **monolítica modular** organizada em camadas bem definidas:

```
┌─────────────────────────────────────────────────┐
│                 HTTP Layer                       │
│        (Controllers, Requests, Resources)        │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│               Service Layer                      │
│     (TransferService, NotificationService)      │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│              Domain Layer                        │
│         (Models, Business Rules)                │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│           Infrastructure Layer                   │
│    (Database, Cache, Queue, External APIs)      │
└──────────────────────────────────────────────────┘
```

### Componentes Principais

- **Controllers**: Entrada HTTP e validação de requisições
- **Services**: Lógica de negócio e orquestração
- **Models**: Entidades de domínio e relacionamentos
- **Jobs**: Processamento assíncrono e tarefas em background
- **Resources**: Serialização padronizada de respostas

## 🎓 Conceitos Técnicos

### Sistema de Cache em Múltiplas Camadas

```
┌─────────────────────────────────────────────┐
│          Cache de Listagens (5min)          │ ← Queries complexas
├─────────────────────────────────────────────┤
│        Cache de Entidades (10-15min)        │ ← Registros individuais
├─────────────────────────────────────────────┤
│      Cache de Agregações (2min)             │ ← Dados financeiros
└─────────────────────────────────────────────┘
```

### Processamento Assíncrono Resiliente

```
Transfer → Queue → Job → [Retry 1] → [Retry 2] → [Retry 3] → Failed
           ↓                ↑            ↑            ↑
      Notification      1min delay   5min delay   15min delay
```

### Transações Atômicas com Lock

```sql
BEGIN TRANSACTION;
  SELECT * FROM users WHERE id = ? FOR UPDATE; -- Lock pessimista
  UPDATE users SET balance = balance - ? WHERE id = ?;
  UPDATE users SET balance = balance + ? WHERE id = ?;
  INSERT INTO transactions ...;
COMMIT;
```

## 📊 Performance

| Operação | Sem Cache | Com Cache | Ganho |
|----------|-----------|-----------|-------|
| Listagem de transações | 320ms | 12ms | **96%** ⚡ |
| Estatísticas agregadas | 580ms | 8ms | **98%** ⚡ |
| Transferência completa | 1350ms | 155ms | **87%** ⚡ |

## 🔐 Segurança

- ✅ Rate limiting por IP e endpoint
- ✅ Validação de entrada robusta (Form Requests)
- ✅ Proteção contra SQL Injection (Eloquent ORM)
- ✅ Hashing seguro de senhas (bcrypt)
- ✅ Lock pessimista para evitar race conditions
- ✅ Transações atômicas com rollback automático
- ✅ Sanitização de dados de saída
- ✅ CORS configurado adequadamente

## 📈 Observabilidade

### Logs Estruturados
```json
{
  "timestamp": "2024-12-24T10:30:00Z",
  "level": "info",
  "context": "transfer",
  "transaction_id": 123,
  "payer_id": 1,
  "payee_id": 2,
  "amount": 100.00,
  "duration_ms": 155
}
```

### Métricas Coletadas
- Tempo de resposta por endpoint
- Taxa de sucesso de transferências
- Taxa de sucesso de notificações
- Uso de cache (hits/misses)
- Tamanho da fila de jobs

## 🧪 Testes

```bash
# Executar todos os testes
php artisan test

# Com cobertura
php artisan test --coverage

# Testes específicos
php artisan test --filter=TransferTest
```

## 🚀 Deploy

### Requisitos Mínimos
- **CPU**: 2 vCPUs
- **RAM**: 2GB
- **Disco**: 20GB SSD
- **PHP**: 8.1+
- **MySQL**: 5.7+
- **Redis**: 6.0+ (opcional)

### Variáveis de Ambiente Críticas

```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simple_payment
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 📞 Suporte

- **Issues**: [GitHub Issues](https://github.com/seu-usuario/simple-payment/issues)
- **Discussões**: [GitHub Discussions](https://github.com/seu-usuario/simple-payment/discussions)
- **Email**: suporte@exemplo.com

## 📄 Licença

Este projeto é um desafio técnico e está disponível para fins educacionais.

---

⭐ **Dica**: Comece pelo [Guia de Instalação](INSTALACAO.md) e depois explore a [Documentação da API](API.md).
