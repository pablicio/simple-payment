# 📚 Documentação da API - payment Simplificado

## 📋 Índice
- [Controllers](#controllers)
- [Services](#services)
- [Models](#models)

---

## 🎮 Controllers

### TransferController

**Responsabilidade:** Gerencia as requisições HTTP relacionadas a transferências entre usuários.

#### `transfer(Request $request)`
**Método:** `POST /api/transfer`

Processa uma transferência de dinheiro entre dois usuários.

**Request Body:**
```json
{
  "value": 100.00,
  "payer": 1,
  "payee": 2
}
```

**Validações:**
- `value`: obrigatório, numérico, mínimo 0.01
- `payer`: obrigatório, inteiro, deve existir na tabela users
- `payee`: obrigatório, inteiro, deve existir na tabela users

**Responses:**

✅ **Sucesso (201):**
```json
{
  "message": "Transfer completed successfully",
  "data": {
    "transaction_id": 1,
    "payer": {
      "id": 1,
      "name": "João Silva",
      "balance": "400.00"
    },
    "payee": {
      "id": 2,
      "name": "Maria Santos",
      "balance": "600.00"
    },
    "amount": "100.00",
    "status": "completed",
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

❌ **Erro de Validação (422):**
```json
{
  "message": "Validation error",
  "errors": {
    "value": ["The value field is required."]
  }
}
```

❌ **Erro de Negócio (400):**
```json
{
  "message": "Insufficient balance"
}
```

---

### UserController

**Responsabilidade:** Gerencia o CRUD de usuários do sistema.

#### `index()`
**Método:** `GET /api/users`

Lista todos os usuários cadastrados.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@email.com",
      "document": "12345678900",
      "type": "common",
      "balance": "500.00"
    }
  ]
}
```

---

#### `store(Request $request)`
**Método:** `POST /api/users`

Cria um novo usuário no sistema.

**Request Body:**
```json
{
  "name": "João Silva",
  "email": "joao@email.com",
  "document": "12345678900",
  "password": "senha123",
  "type": "common",
  "balance": 500.00
}
```

**Validações:**
- `name`: obrigatório, string, máximo 255 caracteres
- `email`: obrigatório, email válido, único
- `document`: obrigatório, string, único (CPF/CNPJ)
- `password`: obrigatório, mínimo 6 caracteres
- `type`: obrigatório, valores aceitos: `common` ou `merchant`
- `balance`: opcional, numérico, mínimo 0

**Response (201):**
```json
{
  "message": "User created successfully",
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "document": "12345678900",
    "type": "common",
    "balance": "500.00"
  }
}
```

---

#### `show(int $id)`
**Método:** `GET /api/users/{id}`

Retorna os dados de um usuário específico.

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "document": "12345678900",
    "type": "common",
    "balance": "500.00"
  }
}
```

**Response (404):**
```json
{
  "message": "User not found"
}
```

---

#### `update(Request $request, int $id)`
**Método:** `PUT /api/users/{id}`

Atualiza os dados de um usuário existente.

**Request Body (todos os campos são opcionais):**
```json
{
  "name": "João Silva Atualizado",
  "email": "novoemail@email.com",
  "balance": 1000.00
}
```

**Response (200):**
```json
{
  "message": "User updated successfully",
  "data": {
    "id": 1,
    "name": "João Silva Atualizado",
    "email": "novoemail@email.com",
    "document": "12345678900",
    "type": "common",
    "balance": "1000.00"
  }
}
```

---

#### `destroy(int $id)`
**Método:** `DELETE /api/users/{id}`

Remove um usuário do sistema.

**Response (200):**
```json
{
  "message": "User deleted successfully"
}
```

---

## ⚙️ Services

### TransferService

**Responsabilidade:** Contém toda a lógica de negócio para processar transferências.

#### `transfer(int $payerId, int $payeeId, float $amount): Transaction`

Executa uma transferência completa entre dois usuários.

**Fluxo de Execução:**

1. **Lock Pessimista:** Bloqueia os registros dos usuários para evitar condições de corrida
2. **Validação de Negócio:** Valida todas as regras de transferência
3. **Autorização Externa:** Consulta o serviço autorizador
4. **Criação da Transação:** Cria registro com status `pending`
5. **Atualização de Saldos:** Debita do pagador e credita ao recebedor
6. **Conclusão:** Marca transação como `completed`
7. **Notificação:** Envia notificação ao recebedor (não bloqueante)

**Retorna:** Objeto `Transaction` com relacionamentos carregados

**Exceções:**
- `Merchants cannot send transfers` - Lojista tentou enviar dinheiro
- `Cannot transfer to yourself` - Tentativa de transferência para si mesmo
- `Amount must be greater than zero` - Valor inválido
- `Insufficient balance` - Saldo insuficiente
- `Transfer not authorized` - Serviço autorizador negou

---

#### `validateTransfer(User $payer, User $payee, float $amount): void`

Valida as regras de negócio antes de processar a transferência.

**Validações:**
- ✅ Apenas usuários comuns podem enviar
- ✅ Não pode transferir para si mesmo
- ✅ Valor deve ser positivo
- ✅ Saldo deve ser suficiente

---

#### `authorize(): bool`

Consulta o serviço autorizador externo.

**URL:** `https://util.devi.tools/api/v2/authorize`  
**Método:** `GET`  
**Timeout:** 5 segundos

**Retorna:**
- `true` - Transferência autorizada
- `false` - Transferência negada ou serviço indisponível

---

#### `notifyPayee(User $payee, User $payer, float $amount): void`

Envia notificação ao recebedor (execução assíncrona).

**URL:** `https://util.devi.tools/api/v1/notify`  
**Método:** `POST`  
**Timeout:** 3 segundos

**Payload:**
```json
{
  "email": "recebedor@email.com",
  "message": "Você recebeu R$ 100.00 de João Silva"
}
```

**Observação:** Falhas na notificação não impedem a transferência.

---

## 📦 Models

### User

**Tabela:** `users`

Representa um usuário do sistema (comum ou lojista).

**Constantes:**
- `TYPE_COMMON = 'common'` - Usuário comum
- `TYPE_MERCHANT = 'merchant'` - Lojista

**Atributos:**
```php
protected $fillable = [
    'name',      // Nome completo
    'email',     // Email único
    'document',  // CPF ou CNPJ (único)
    'password',  // Senha (hash)
    'type',      // Tipo: common ou merchant
    'balance',   // Saldo disponível
];
```

**Relacionamentos:**

- `sentTransactions()` - Transferências enviadas pelo usuário
- `receivedTransactions()` - Transferências recebidas pelo usuário

**Métodos:**

#### `isMerchant(): bool`
Verifica se o usuário é um lojista.

#### `canSendTransfer(): bool`
Verifica se o usuário pode enviar transferências (apenas usuários comuns podem).

#### `hasSufficientBalance(float $amount): bool`
Verifica se o usuário tem saldo suficiente para uma transferência.

---

### Transaction

**Tabela:** `transactions`

Representa uma transação de transferência entre usuários.

**Constantes:**
- `STATUS_PENDING = 'pending'` - Aguardando conclusão
- `STATUS_COMPLETED = 'completed'` - Transferência concluída
- `STATUS_FAILED = 'failed'` - Transferência falhou

**Atributos:**
```php
protected $fillable = [
    'payer_id',     // ID do usuário que envia
    'payee_id',     // ID do usuário que recebe
    'amount',       // Valor da transferência
    'status',       // Status da transação
    'description',  // Descrição opcional
];
```

**Relacionamentos:**

- `payer()` - Usuário que está enviando o dinheiro
- `payee()` - Usuário que está recebendo o dinheiro

**Métodos:**

#### `markAsCompleted(): void`
Marca a transação como concluída.

#### `markAsFailed(): void`
Marca a transação como falha.

#### `isPending(): bool`
Verifica se a transação está pendente.

---

## 🔒 Segurança e Boas Práticas

### Transações Database
Todas as transferências são executadas dentro de uma transação database (`DB::transaction()`), garantindo que:
- Ou tudo é executado com sucesso
- Ou tudo é revertido em caso de erro

### Lock Pessimista
Utiliza `lockForUpdate()` para evitar condições de corrida quando múltiplas transferências envolvem os mesmos usuários simultaneamente.

### Validação em Camadas
- **Controller:** Validação de formato e tipos de dados
- **Service:** Validação de regras de negócio
- **Model:** Métodos auxiliares para validação de estado

### Tratamento de Erros
- Exceções específicas para cada tipo de erro
- Logs para falhas em serviços externos
- Respostas HTTP padronizadas

---

## 📝 Exemplos de Uso

### Criar um Usuário Comum
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@email.com",
    "document": "12345678900",
    "password": "senha123",
    "type": "common",
    "balance": 500.00
  }'
```

### Criar um Lojista
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Loja ABC",
    "email": "loja@email.com",
    "document": "12345678000100",
    "password": "senha123",
    "type": "merchant",
    "balance": 0
  }'
```

### Realizar uma Transferência
```bash
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.00,
    "payer": 1,
    "payee": 2
  }'
```

### Listar Todos os Usuários
```bash
curl -X GET http://localhost:8000/api/users
```

### Consultar Usuário Específico
```bash
curl -X GET http://localhost:8000/api/users/1
```
