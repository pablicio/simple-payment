# 🧪 TESTES VÁLIDOS - Payment System

## 📊 Dados no Banco (após seed)

### 👥 Usuários Comuns (podem ENVIAR e RECEBER)
```
ID  | Nome           | Email                  | Saldo
----|----------------|------------------------|----------
1   | João Silva     | joao@example.com       | R$ 1.000,00
2   | Maria Santos   | maria@example.com      | R$ 1.500,50
3   | Pedro Oliveira | pedro@example.com      | R$ 500,00
4   | Ana Costa      | ana@example.com        | R$ 2.500,00
5   | Carlos Ferreira| carlos@example.com     | R$ 750,25
```

### 🏪 Lojistas (podem APENAS RECEBER)
```
ID  | Nome                    | Email                          | Saldo
----|-------------------------|--------------------------------|----------
6   | Loja ABC Ltda          | contato@lojaabc.com            | R$ 5.000,00
7   | Supermercado Central   | vendas@supercentral.com        | R$ 15.000,00
8   | Tech Store             | contato@techstore.com          | R$ 8.500,50
9   | Restaurante Sabor Arte | pedidos@saborarte.com          | R$ 3.200,00
10  | Farmácia Saúde         | atendimento@farmaciasaude.com  | R$ 6.800,75
```

---

## ✅ TESTES VÁLIDOS

### 1️⃣ Transferência Usuário → Usuário (P2P)

```bash
# João envia R$ 50 para Maria
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 50, "payer": 1, "payee": 2}'
```

```bash
# Maria envia R$ 100 para Pedro
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100, "payer": 2, "payee": 3}'
```

```bash
# Ana envia R$ 200 para Carlos
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 200, "payer": 4, "payee": 5}'
```

### 2️⃣ Pagamento Usuário → Lojista

```bash
# João paga R$ 80 na Loja ABC
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 80, "payer": 1, "payee": 6}'
```

```bash
# Maria compra R$ 150 no Supermercado
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 150, "payer": 2, "payee": 7}'
```

```bash
# Pedro compra R$ 300 na Tech Store
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 300, "payer": 3, "payee": 8}'
```

### 3️⃣ Valores Pequenos (centavos)

```bash
# João envia R$ 0,50 para Maria
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 0.50, "payer": 1, "payee": 2}'
```

```bash
# Maria envia R$ 5,99 para Pedro
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 5.99, "payer": 2, "payee": 3}'
```

---

## ❌ TESTES QUE DEVEM FALHAR

### 1️⃣ Lojista tentando enviar (NÃO PERMITIDO)

```bash
# ❌ Loja ABC tenta enviar para João
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 10, "payer": 6, "payee": 1}'
```
**Erro esperado:** `"Merchants cannot send transfers"`

### 2️⃣ Saldo insuficiente

```bash
# ❌ Pedro (R$ 500) tenta enviar R$ 10.000
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 10000, "payer": 3, "payee": 1}'
```
**Erro esperado:** `"Insufficient balance"`

### 3️⃣ Transferir para si mesmo

```bash
# ❌ João tenta enviar para ele mesmo
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 10, "payer": 1, "payee": 1}'
```
**Erro esperado:** `"Cannot transfer to yourself"`

### 4️⃣ Valor inválido (zero ou negativo)

```bash
# ❌ Valor zero
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 0, "payer": 1, "payee": 2}'
```
**Erro esperado:** `"Amount must be greater than zero"`

```bash
# ❌ Valor negativo
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": -50, "payer": 1, "payee": 2}'
```
**Erro esperado:** Validation error

### 5️⃣ Usuário não existe

```bash
# ❌ ID 999 não existe
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 10, "payer": 999, "payee": 1}'
```
**Erro esperado:** `"Validation error"`

---

## ⚠️ SOBRE O ERRO "Transfer not authorized"

Este erro ocorre porque a API chama um **serviço autorizador externo**:
```
https://util.devi.tools/api/v2/authorize
```

### Por que falha?
1. API externa pode estar fora do ar
2. API externa retorna negação aleatória
3. Timeout de rede (5 segundos)

### Soluções:

#### Opção 1: Desabilitar temporariamente (para testes)
Edite `app/Services/TransferService.php` e troque:
```php
private function authorize(): bool
{
    return true; // Sempre autoriza para testes
}
```

#### Opção 2: Configurar mock
Edite `.env` e adicione:
```env
TRANSFER_AUTHORIZER_MOCK=true
```

#### Opção 3: Tentar várias vezes
A API externa é instável, tente 2-3 vezes o mesmo teste.

---

## 🔍 Verificar Dados

### Ver todos os usuários
```bash
curl http://localhost:8000/api/users
```

### Ver usuário específico
```bash
curl http://localhost:8000/api/users/1
```

### Ver saldo atualizado
```bash
# Antes da transferência
curl http://localhost:8000/api/users/1

# Fazer transferência
curl -X POST http://localhost:8000/api/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 10, "payer": 1, "payee": 2}'

# Depois da transferência (saldo deve ter diminuído)
curl http://localhost:8000/api/users/1
curl http://localhost:8000/api/users/2
```

### Ver transações
```bash
curl http://localhost:8000/api/transactions
```

---

## 🧪 Script de Teste Automatizado

Salve como `test-api.bat`:

```batch
@echo off
echo Testando API...
echo.

echo [1] Listar usuarios
curl http://localhost:8000/api/users
echo.

echo [2] Transferencia valida (Joao → Maria)
curl -X POST http://localhost:8000/api/transfer -H "Content-Type: application/json" -d "{\"value\": 10, \"payer\": 1, \"payee\": 2}"
echo.

echo [3] Pagamento valido (Maria → Loja)
curl -X POST http://localhost:8000/api/transfer -H "Content-Type: application/json" -d "{\"value\": 20, \"payer\": 2, \"payee\": 6}"
echo.

echo [4] Erro: Lojista enviando
curl -X POST http://localhost:8000/api/transfer -H "Content-Type: application/json" -d "{\"value\": 10, \"payer\": 6, \"payee\": 1}"
echo.

echo [5] Ver transacoes
curl http://localhost:8000/api/transactions
echo.

pause
```

---

## 📋 Resumo

**IDs válidos para PAYER (enviar):** 1, 2, 3, 4, 5 (usuários comuns)
**IDs válidos para PAYEE (receber):** 1-10 (todos)
**Valor mínimo:** 0.01
**Formato do valor:** decimal com ponto (não vírgula)

**Combinações válidas:**
- ✅ Comum → Comum (1→2, 2→3, etc)
- ✅ Comum → Lojista (1→6, 2→7, etc)
- ❌ Lojista → Qualquer (6→1, 7→2, etc) - BLOQUEADO
- ❌ Qualquer → Mesmo (1→1, 2→2, etc) - BLOQUEADO
