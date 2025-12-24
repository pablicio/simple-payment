# 📊 Sistema de Observabilidade

Documentação completa do sistema de logs, métricas e rastreamento do Simple Payment.

## 📑 Índice

- [Visão Geral](#visão-geral)
- [Logs Estruturados](#logs-estruturados)
- [Métricas e Monitoramento](#métricas-e-monitoramento)
- [Rastreamento de Requisições](#rastreamento-de-requisições)
- [Alertas](#alertas)
- [Dashboards](#dashboards)

## 🎯 Visão Geral

O sistema implementa observabilidade em três pilares:

1. **Logs**: Eventos estruturados com contexto rico
2. **Métricas**: Números que medem performance e saúde
3. **Traces**: Rastreamento de requisições através do sistema

### Stack de Observabilidade

```
┌─────────────────────────────────────────────────┐
│                  Application                     │
│         (Laravel + Custom Logging)              │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│              Log Aggregation                     │
│    (Laravel Log → JSON → File/Syslog)           │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│           Metrics Collection                     │
│        (Custom Middleware + Jobs)               │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│          Visualization Layer                     │
│     (Recomendado: Grafana/Kibana)               │
└──────────────────────────────────────────────────┘
```

## 📝 Logs Estruturados

### Níveis de Log

| Nível | Uso | Exemplo |
|-------|-----|---------|
| **DEBUG** | Informações detalhadas para debug | Valores de variáveis, estados internos |
| **INFO** | Eventos normais do sistema | Transferência iniciada, notificação enviada |
| **WARNING** | Situações anormais mas recuperáveis | Retry de notificação, timeout de API |
| **ERROR** | Erros que impedem uma operação | Falha em transferência, DB down |
| **CRITICAL** | Erros que afetam todo o sistema | Banco de dados inacessível |

### Estrutura de Log Padrão

```json
{
  "timestamp": "2024-12-24T10:30:00.123456Z",
  "level": "info",
  "message": "Transfer completed successfully",
  "context": {
    "transaction_id": 123,
    "payer_id": 1,
    "payee_id": 2,
    "amount": 100.00,
    "duration_ms": 155,
    "request_id": "req-abc123",
    "user_ip": "192.168.1.1"
  },
  "extra": {
    "memory_peak_mb": 12.5,
    "queries_count": 8
  }
}
```

### Logs por Contexto

#### 1. Transferências

```php
// Início da transferência
Log::info('Transfer initiated', [
    'payer_id' => $payerId,
    'payee_id' => $payeeId,
    'amount' => $amount,
    'request_id' => $requestId
]);

// Transferência concluída
Log::info('Transfer completed', [
    'transaction_id' => $transaction->id,
    'duration_ms' => $duration,
    'cache_used' => $cacheHit
]);

// Erro na transferência
Log::error('Transfer failed', [
    'payer_id' => $payerId,
    'payee_id' => $payeeId,
    'amount' => $amount,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

#### 2. Autorizador Externo

```php
// Chamada ao autorizador
Log::info('Authorizer request sent', [
    'url' => $authorizerUrl,
    'request_id' => $requestId
]);

// Resposta do autorizador
Log::info('Authorizer response received', [
    'authorized' => $authorized,
    'response_time_ms' => $responseTime,
    'status_code' => $statusCode
]);

// Falha no autorizador
Log::warning('Authorizer failed', [
    'error' => $e->getMessage(),
    'fallback' => 'denying',
    'retry_count' => $retryCount
]);
```

#### 3. Notificações

```php
// Notificação despachada
Log::info('Notification dispatched', [
    'notification_id' => $notification->id,
    'transaction_id' => $transaction->id,
    'user_id' => $userId,
    'queue' => $queueName
]);

// Notificação enviada
Log::info('Notification sent', [
    'notification_id' => $notification->id,
    'attempt' => $attemptNumber,
    'duration_ms' => $duration
]);

// Retry de notificação
Log::warning('Notification retry scheduled', [
    'notification_id' => $notification->id,
    'attempt' => $attemptNumber,
    'next_retry_at' => $nextRetryTime
]);
```

#### 4. Cache

```php
// Cache hit
Log::debug('Cache hit', [
    'key' => $cacheKey,
    'ttl_remaining_seconds' => $ttlRemaining
]);

// Cache miss
Log::debug('Cache miss', [
    'key' => $cacheKey,
    'regenerating' => true
]);

// Cache invalidado
Log::info('Cache invalidated', [
    'keys' => $invalidatedKeys,
    'reason' => 'user_update'
]);
```

### Configuração de Logs

**config/logging.php**
```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],

    'structured' => [
        'driver' => 'monolog',
        'handler' => StreamHandler::class,
        'formatter' => JsonFormatter::class,
        'with' => [
            'stream' => storage_path('logs/structured.log'),
        ],
    ],
],
```

## 📈 Métricas e Monitoramento

### Métricas de Performance

#### Tempo de Resposta

```php
// Métricas coletadas automaticamente via Middleware
[
    'endpoint' => 'POST /api/transfer',
    'response_time_ms' => 155,
    'response_time_p50' => 150,  // Mediana
    'response_time_p95' => 200,  // 95º percentil
    'response_time_p99' => 300,  // 99º percentil
]
```

#### Taxa de Sucesso

```php
[
    'transfers_total' => 1000,
    'transfers_success' => 980,
    'transfers_failed' => 20,
    'success_rate' => 98.0,  // %
]
```

#### Cache Performance

```php
[
    'cache_hits' => 8500,
    'cache_misses' => 1500,
    'cache_hit_rate' => 85.0,  // %
    'cache_size_mb' => 120.5,
]
```

### Métricas de Sistema

#### Uso de Recursos

```php
[
    'memory_usage_mb' => 45.2,
    'memory_peak_mb' => 52.8,
    'cpu_usage_percent' => 35.5,
    'disk_usage_percent' => 42.0,
]
```

#### Queue e Jobs

```php
[
    'queue_size' => 150,           // Jobs pendentes
    'queue_processing' => 5,        // Jobs em processamento
    'queue_failed' => 3,            // Jobs falhados
    'avg_job_time_seconds' => 2.5,
]
```

### Métricas de Negócio

#### Transações

```php
[
    'transactions_per_hour' => 450,
    'transaction_volume_total' => 125000.00,
    'transaction_volume_avg' => 277.78,
    'unique_users_active' => 320,
]
```

#### Notificações

```php
[
    'notifications_sent' => 980,
    'notifications_failed' => 20,
    'notification_success_rate' => 98.0,
    'avg_retry_count' => 1.2,
]
```

### Dashboard de Métricas

Recomendamos integração com Grafana usando os seguintes painéis:

#### 1. Performance Overview
- Tempo de resposta por endpoint (gráfico de linha)
- Taxa de erro (%)
- Throughput (req/s)
- Latência P50, P95, P99

#### 2. Business Metrics
- Volume de transações (R$)
- Número de transações
- Usuários ativos
- Taxa de conversão

#### 3. System Health
- CPU e Memória
- Tamanho da fila
- Cache hit rate
- Database connections

#### 4. Errors & Alerts
- Erros por tipo
- Taxa de retry
- Falhas de integração
- Timeouts

## 🔍 Rastreamento de Requisições

### Request ID

Cada requisição recebe um ID único para rastreamento:

```php
// Middleware adiciona header
'X-Request-ID' => Str::uuid()->toString()
```

### Contexto de Rastreamento

```php
Log::withContext([
    'request_id' => $requestId,
    'user_id' => $userId,
    'ip' => $request->ip(),
])->info('Processing transfer');
```

### Rastreamento End-to-End

```
[req-abc123] HTTP Request received → POST /api/transfer
[req-abc123] Transfer validation started
[req-abc123] Transfer validation passed
[req-abc123] Database transaction started
[req-abc123] External authorizer called
[req-abc123] External authorizer approved
[req-abc123] Balance updated
[req-abc123] Transaction created (id: 123)
[req-abc123] Database transaction committed
[req-abc123] Notification job dispatched
[req-abc123] Cache invalidated
[req-abc123] HTTP Response sent (201) - 155ms
```

## 🚨 Alertas

### Configuração de Alertas

#### 1. Alertas Críticos (Pager/SMS)

```yaml
alerts:
  - name: "Database Down"
    condition: database_connection == false
    duration: 1m
    severity: critical
    
  - name: "High Error Rate"
    condition: error_rate > 5%
    duration: 5m
    severity: critical
    
  - name: "Queue Overload"
    condition: queue_size > 10000
    duration: 10m
    severity: critical
```

#### 2. Alertas de Warning (Email/Slack)

```yaml
alerts:
  - name: "Slow Response Time"
    condition: response_time_p95 > 2000ms
    duration: 10m
    severity: warning
    
  - name: "Cache Hit Rate Low"
    condition: cache_hit_rate < 70%
    duration: 15m
    severity: warning
    
  - name: "High Notification Failure Rate"
    condition: notification_failure_rate > 10%
    duration: 10m
    severity: warning
```

### Canais de Notificação

**config/logging.php**
```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'Simple Payment',
    'emoji' => ':warning:',
    'level' => 'error',
],
```

## 📊 Queries e Análises

### Análise de Performance

```bash
# Top 10 endpoints mais lentos
cat storage/logs/structured.log | jq -r 'select(.context.duration_ms > 1000) | "\(.context.duration_ms)ms - \(.context.endpoint)"' | sort -rn | head -10

# Taxa de erro por hora
cat storage/logs/structured.log | jq -r 'select(.level == "error") | .timestamp' | cut -d: -f1 | sort | uniq -c

# Cache hit rate
cat storage/logs/structured.log | jq -r 'select(.message | contains("Cache")) | .message' | sort | uniq -c
```

### Análise de Negócio

```bash
# Volume de transações por dia
cat storage/logs/structured.log | jq -r 'select(.message == "Transfer completed") | "\(.timestamp | split("T")[0]) \(.context.amount)"' | awk '{sum[$1]+=$2; count[$1]++} END {for(day in sum) print day, sum[day], count[day]}'

# Top usuários por volume
cat storage/logs/structured.log | jq -r 'select(.message == "Transfer completed") | "\(.context.payer_id) \(.context.amount)"' | awk '{sum[$1]+=$2} END {for(user in sum) print user, sum[user]}' | sort -k2 -rn | head -10
```

## 🔧 Configuração de Produção

### Variáveis de Ambiente

```bash
# Nível de log (debug, info, warning, error)
LOG_LEVEL=warning

# Canal de log
LOG_CHANNEL=stack

# Deprecations (none, null, log, logs)
LOG_DEPRECATIONS_CHANNEL=null

# Slack webhook para erros críticos
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...

# Sentry para rastreamento de erros
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

### Retenção de Logs

```php
// Configurar retenção em config/logging.php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'info'),
    'days' => 14,  // Manter logs por 14 dias
],
```

### Rotação de Logs

```bash
# /etc/logrotate.d/simple-payment
/var/www/simple-payment/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

## 🎯 Melhores Práticas

### 1. Use Contexto Rico

```php
// ❌ Ruim
Log::info('Transfer failed');

// ✅ Bom
Log::info('Transfer failed', [
    'transaction_id' => $transaction->id,
    'payer_id' => $payer->id,
    'payee_id' => $payee->id,
    'amount' => $amount,
    'error' => $e->getMessage(),
]);
```

### 2. Níveis de Log Apropriados

```php
// DEBUG - Apenas em desenvolvimento
Log::debug('Variable value', ['value' => $var]);

// INFO - Eventos normais
Log::info('Transfer completed', $context);

// WARNING - Situações anormais
Log::warning('Retry scheduled', $context);

// ERROR - Falhas operacionais
Log::error('Transfer failed', $context);

// CRITICAL - Sistema comprometido
Log::critical('Database connection lost', $context);
```

### 3. Dados Sensíveis

```php
// ❌ Nunca logar dados sensíveis
Log::info('User data', [
    'password' => $password,  // NUNCA!
    'credit_card' => $card,   // NUNCA!
]);

// ✅ Logar apenas IDs e dados não sensíveis
Log::info('User data', [
    'user_id' => $userId,
    'has_password' => !empty($password),
]);
```

### 4. Performance

```php
// ❌ Evite logs excessivos em loops
foreach ($users as $user) {
    Log::debug('Processing user', ['user_id' => $user->id]);
    // ...
}

// ✅ Log agregado
Log::debug('Processing users', [
    'count' => count($users),
    'user_ids' => array_column($users, 'id')
]);
```

## 🚀 Próximos Passos

1. ✅ **Implementado**: Logs estruturados em JSON
2. ✅ **Implementado**: Contexto de rastreamento
3. 📋 **Recomendado**: Integração com Sentry para error tracking
4. 📋 **Recomendado**: Integração com Grafana para visualização
5. 📋 **Recomendado**: APM (New Relic, DataDog)
6. 📋 **Futuro**: Distributed tracing (Jaeger, Zipkin)

---

📚 **Ver também**:
- [Segurança](SEGURANCA.md)
- [Arquitetura](ARQUITETURA.md)
- [Performance e Cache](CACHE_SYSTEM.md)
