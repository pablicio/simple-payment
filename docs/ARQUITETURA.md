# 🏗️ Arquitetura do Sistema

## 📐 Visão Geral

O Payment Simplificado segue uma arquitetura em camadas baseada no padrão MVC (Model-View-Controller) com a adição de uma camada de serviços para lógica de negócio complexa.

```
┌─────────────────────────────────────────┐
│           HTTP Request                   │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│         Routes (API)                     │
│  - /api/users                            │
│  - /api/transfer                         │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│         Controllers                      │
│  - UserController                        │
│  - TransferController                    │
│                                          │
│  Responsabilidade:                       │
│  - Validar requests HTTP                 │
│  - Retornar responses HTTP               │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│         Services                         │
│  - TransferService                       │
│                                          │
│  Responsabilidade:                       │
│  - Lógica de negócio complexa            │
│  - Orquestração de operações             │
│  - Integração com APIs externas          │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│         Models                           │
│  - User                                  │
│  - Transaction                           │
│                                          │
│  Responsabilidade:                       │
│  - Representar entidades                 │
│  - Relacionamentos                       │
│  - Regras simples de validação           │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│         Database (MySQL)                 │
│  - users                                 │
│  - transactions                          │
└─────────────────────────────────────────┘
```

---

## 🎯 Padrões de Design Utilizados

### 1. **Service Layer Pattern**
A camada de serviços (`TransferService`) encapsula a lógica de negócio complexa, mantendo os controllers simples e focados em HTTP.

**Benefícios:**
- Reutilização de código
- Facilita testes unitários
- Separa responsabilidades

### 2. **Repository Pattern (via Eloquent ORM)**
Os Models do Laravel funcionam como repositories, abstraindo o acesso ao banco de dados.

**Benefícios:**
- Código mais limpo
- Fácil troca de banco de dados
- Query builder poderoso

### 3. **Dependency Injection**
O Laravel injeta automaticamente dependências nos construtores.

```php
public function __construct(TransferService $transferService)
{
    $this->transferService = $transferService;
}
```

**Benefícios:**
- Baixo acoplamento
- Facilita testes (mocking)
- Código mais flexível

### 4. **Transaction Script Pattern**
Toda transferência é executada dentro de uma transação database.

```php
DB::transaction(function () {
    // Operações atômicas
});
```

**Benefícios:**
- Garantia de consistência
- Rollback automático em caso de erro
- ACID compliance

---

## 🔄 Fluxo de uma Transferência

```
1. Request HTTP
   POST /api/transfer
   ↓

2. TransferController::transfer()
   - Valida dados de entrada
   - Chama TransferService
   ↓

3. TransferService::transfer()
   
   3.1 Inicia Transação DB
       ↓
   3.2 Lock Pessimista
       User::lockForUpdate()
       ↓
   3.3 Validações de Negócio
       - Lojista não pode enviar
       - Verificar saldo
       - Valor positivo
       ↓
   3.4 Consulta Autorizador Externo
       GET https://util.devi.tools/api/v2/authorize
       ↓
   3.5 Cria Transaction (status: pending)
       ↓
   3.6 Atualiza Saldos
       - Decrementa saldo do payer
       - Incrementa saldo do payee
       ↓
   3.7 Marca como Completed
       ↓
   3.8 Commit da Transação
       ↓
   3.9 Envia Notificação (assíncrono)
       POST https://util.devi.tools/api/v1/notify
   ↓

4. Response HTTP
   201 Created
   { "message": "Transfer completed successfully", ... }
```

---

## 🗄️ Estrutura de Dados

### Tabela: `users`
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    document VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    type ENUM('common', 'merchant') NOT NULL,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Tabela: `transactions`
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payer_id BIGINT UNSIGNED NOT NULL,
    payee_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (payer_id) REFERENCES users(id),
    FOREIGN KEY (payee_id) REFERENCES users(id)
);
```

**Relacionamentos:**
- `users.id` → `transactions.payer_id` (1:N)
- `users.id` → `transactions.payee_id` (1:N)

---

## 🔐 Segurança

### 1. **Concorrência**
- **Lock Pessimista:** Evita race conditions em transferências simultâneas
- **Transações DB:** Garante atomicidade das operações

### 2. **Validação em Camadas**
- **Controller:** Valida formato e tipos
- **Service:** Valida regras de negócio
- **Model:** Valida estado das entidades

### 3. **Proteção de Dados Sensíveis**
- Senha armazenada com hash (bcrypt)
- Campo `password` oculto nas respostas da API
- Validação de unicidade para email e documento

### 4. **Tratamento de Erros**
- Exceptions tipadas para cada erro
- Logs para debugging
- Responses HTTP padronizados

---

## 🚀 Escalabilidade

### Pontos de Melhoria

#### 1. **Fila de Jobs (Queue)**
Processar notificações de forma assíncrona usando Laravel Queues:

```php
// Em vez de chamar diretamente
$this->notifyPayee($payee, $payer, $amount);

// Usar um Job
NotifyPayeeJob::dispatch($payee, $payer, $amount);
```

**Benefício:** Não bloqueia a resposta da transferência.

#### 2. **Cache**
Cachear dados que mudam pouco:

```php
// Cache de configurações
Cache::remember('app_config', 3600, function() {
    return Config::all();
});
```

#### 3. **Database Read Replicas**
Separar leituras de escritas para melhor performance:

```php
// Escrita no master
User::create($data);

// Leitura em replica
User::onReadConnection()->get();
```

#### 4. **Rate Limiting**
Proteger contra abuso da API:

```php
Route::middleware('throttle:60,1')->group(function () {
    // Máximo 60 requests por minuto
});
```

#### 5. **Event Sourcing**
Para auditoria completa, armazenar todos os eventos:

```php
event(new TransferCreated($transaction));
event(new TransferCompleted($transaction));
```

---

## 🧪 Testabilidade

A arquitetura facilita testes em todos os níveis:

### Testes Unitários (Services)
```php
public function test_transfer_validates_insufficient_balance()
{
    $payer = User::factory()->create(['balance' => 50]);
    $payee = User::factory()->create();
    
    $this->expectException(\Exception::class);
    
    $this->service->transfer($payer->id, $payee->id, 100);
}
```

### Testes de Integração (Controllers)
```php
public function test_transfer_endpoint_success()
{
    $payer = User::factory()->create(['balance' => 500]);
    $payee = User::factory()->create();
    
    $response = $this->postJson('/api/transfer', [
        'value' => 100,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);
    
    $response->assertStatus(201);
}
```

### Testes Feature (Fluxo Completo)
```php
public function test_complete_transfer_flow()
{
    Http::fake([
        'util.devi.tools/api/v2/authorize' => Http::response(['status' => 'success']),
        'util.devi.tools/api/v1/notify' => Http::response(['success' => true]),
    ]);
    
    // Teste do fluxo completo
}
```

---

## 📊 Métricas e Observabilidade

### Logs Importantes
```php
// Logs de erro
\Log::error('Transfer failed', [
    'payer_id' => $payerId,
    'payee_id' => $payeeId,
    'error' => $e->getMessage(),
]);

// Logs de warning
\Log::warning('Notification failed', [
    'payee_id' => $payee->id,
    'error' => $e->getMessage(),
]);
```

### Métricas Recomendadas
- Taxa de sucesso de transferências
- Tempo médio de resposta
- Taxa de falha do autorizador externo
- Taxa de falha de notificações
- Volume de transferências por hora

---

## 🔧 Tecnologias Utilizadas

- **Framework:** Laravel 11.x
- **Linguagem:** PHP 8.2+
- **Banco de Dados:** MySQL 8.0
- **HTTP Client:** Guzzle (via Laravel HTTP)
- **ORM:** Eloquent
- **Validação:** Laravel Validation
- **Transações:** Database Transactions

---

## 📝 Decisões Arquiteturais

### Por que Service Layer?
✅ Separação clara de responsabilidades  
✅ Lógica de negócio reutilizável  
✅ Controllers mais simples  
✅ Facilita testes

### Por que não Repository Pattern explícito?
O Eloquent já funciona como um Repository muito eficiente. Adicionar uma camada extra seria over-engineering para este projeto.

### Por que Notificação não bloqueante?
Falhas na notificação não devem impedir a transferência. A transferência financeira é mais crítica que a notificação.

### Por que Lock Pessimista?
Garante que não haverá race conditions em transferências simultâneas envolvendo os mesmos usuários. É mais seguro que lock otimista para operações financeiras.
