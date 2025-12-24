# Sistema de Cache - Simple Payment API

## Visão Geral

O sistema de cache foi implementado seguindo as melhores práticas do Laravel, com estratégias diferenciadas baseadas na natureza e frequência de mudança dos dados.

## Estratégias de Cache

### 1. **TransactionController**

#### Listagem de Transações (`index`)
- **Chave**: Gerada dinamicamente baseada nos parâmetros da requisição (filtros, paginação)
- **TTL**: 5 minutos
- **Motivo**: Dados mudam frequentemente com novas transações
- **Invalidação**: Automática via tags `['transactions']` após novas transações

#### Transação Individual (`show`)
- **Chave**: `transaction:{id}`
- **TTL**: 10 minutos
- **Motivo**: Transações individuais não mudam após criação
- **Invalidação**: Manual após atualização de status

#### Estatísticas de Usuário (`userStats`)
- **Chave**: `transaction:user:{userId}:stats`
- **TTL**: 15 minutos
- **Motivo**: Agregações custosas que mudam com menor frequência
- **Invalidação**: Após cada transferência envolvendo o usuário

### 2. **UserController**

#### Listagem de Usuários (`index`)
- **Chave**: `users:all`
- **TTL**: 10 minutos
- **Motivo**: Lista completa muda apenas com CRUD de usuários
- **Invalidação**: Após create/update/delete de usuários

#### Usuário Individual (`show`)
- **Chave**: `user:{id}`
- **TTL**: 15 minutos
- **Motivo**: Dados de perfil mudam raramente
- **Invalidação**: Após update/delete do usuário

#### Saldo do Usuário (`balance`)
- **Chave**: `user:{id}:balance`
- **TTL**: 2 minutos (curto!)
- **Motivo**: Dados financeiros mudam frequentemente com transferências
- **Invalidação**: Após cada transferência

### 3. **TransferService**

Após cada transferência bem-sucedida, invalida automaticamente:
- Cache dos usuários envolvidos (payer e payee)
- Cache dos saldos
- Cache de estatísticas de transações
- Cache da listagem de transações (via tags)

## Estrutura de Chaves de Cache

```
Padrão geral: {entidade}:{id}:{contexto}

Exemplos:
- user:123                          # Dados do usuário
- user:123:balance                  # Saldo do usuário
- transaction:456                   # Dados da transação
- transaction:user:123:stats        # Estatísticas do usuário
- transactions:list:{hash}          # Listagem com filtros
- users:all                         # Todos os usuários
```

## Boas Práticas Implementadas

### 1. **TTL Baseado na Volatilidade**
- Dados financeiros (saldo): 2 minutos
- Dados transacionais (listagens): 5 minutos
- Dados de perfil: 10-15 minutos
- Agregações custosas: 15 minutos

### 2. **Invalidação Estratégica**
```php
// Sempre invalida após mudanças
private function invalidateCache(?int $userId = null): void
{
    Cache::forget('users:all');
    if ($userId) {
        Cache::forget("user:{$userId}");
        Cache::forget("user:{$userId}:balance");
    }
}
```

### 3. **Cache por Requisição**
```php
// Chave única baseada em filtros
private function generateCacheKey(Request $request): string
{
    $params = array_filter([
        'status' => $request->get('status'),
        'payer_id' => $request->get('payer_id'),
        // ... outros filtros
    ]);
    return 'transactions:list:' . md5(json_encode($params));
}
```

### 4. **Cache Tags (quando disponível)**
```php
// Facilita invalidação em massa
Cache::tags(['transactions'])->flush();
```

## Recursos Implementados

### TransactionResource
Serialização padronizada de transações com relacionamentos:
```json
{
  "id": 1,
  "payer": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "payee": { /* ... */ },
  "value": 100.00,
  "status": "completed",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### TransactionCollection
Serialização de coleções com paginação:
```json
{
  "data": [ /* ... */ ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

## Novos Endpoints

### Estatísticas de Transações
```
GET /api/transactions/user/{userId}/stats
```

Retorna estatísticas agregadas do usuário com cache de 15 minutos:
```json
{
  "data": {
    "total_sent": 1500.00,
    "total_received": 3200.00,
    "total_transactions_sent": 25,
    "total_transactions_received": 40,
    "pending_transactions": 2
  }
}
```

### Saldo do Usuário
```
GET /api/users/{id}/balance
```

Retorna saldo atual com cache curto (2 minutos):
```json
{
  "data": {
    "user_id": 1,
    "balance": 5430.50
  }
}
```

## Configuração do Driver de Cache

O Laravel suporta vários drivers de cache. Para produção, recomenda-se Redis:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Para desenvolvimento/testes:
```env
CACHE_DRIVER=file
```

## Monitoramento

Para monitorar a eficácia do cache:

1. **Hit Rate**: Quantas requisições são servidas do cache
2. **Miss Rate**: Quantas requisições precisam consultar o banco
3. **Invalidation Rate**: Frequência de invalidações

## Performance Esperada

### Sem Cache
- Listagem de transações: ~200-500ms
- Estatísticas complexas: ~300-800ms
- Consultas simples: ~50-100ms

### Com Cache
- Listagem de transações: ~5-20ms (90-95% mais rápido)
- Estatísticas complexas: ~5-15ms (95-98% mais rápido)
- Consultas simples: ~2-10ms (80-95% mais rápido)

## Considerações de Escalabilidade

1. **Cache Distribuído**: Use Redis para múltiplas instâncias
2. **Cache Warming**: Pré-carregue dados críticos no deploy
3. **Circuit Breaker**: Implemente fallback se cache falhar
4. **Monitoring**: Use Laravel Telescope ou similar para debug

## Limitações e Trade-offs

1. **Consistência Eventual**: Dados podem estar desatualizados por até TTL
2. **Memória**: Cache adiciona uso de memória
3. **Complexidade**: Mais código para manter

## Próximos Passos

- [ ] Implementar Cache Warming para dados críticos
- [ ] Adicionar métricas de cache no monitoramento
- [ ] Implementar cache de queries no Eloquent
- [ ] Considerar cache de view para endpoints públicos
- [ ] Implementar estratégia de cache para relatórios
