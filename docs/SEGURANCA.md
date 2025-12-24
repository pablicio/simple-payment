# 🔐 Segurança

Documentação completa das práticas de segurança implementadas no Simple Payment.

## 📑 Índice

- [Visão Geral](#visão-geral)
- [Autenticação e Autorização](#autenticação-e-autorização)
- [Validação de Dados](#validação-de-dados)
- [Proteção contra Ataques](#proteção-contra-ataques)
- [Segurança de Transações](#segurança-de-transações)
- [Dados Sensíveis](#dados-sensíveis)
- [Rate Limiting](#rate-limiting)
- [Auditoria e Compliance](#auditoria-e-compliance)

## 🎯 Visão Geral

O Simple Payment implementa múltiplas camadas de segurança:

```
┌─────────────────────────────────────────────────┐
│            Rate Limiting Layer                   │ ← Proteção DDoS
├─────────────────────────────────────────────────┤
│         Input Validation Layer                   │ ← Sanitização
├─────────────────────────────────────────────────┤
│        Authorization Layer                       │ ← Controle de acesso
├─────────────────────────────────────────────────┤
│         Business Logic Layer                     │ ← Regras de negócio
├─────────────────────────────────────────────────┤
│          Database Layer                          │ ← Transações ACID
└─────────────────────────────────────────────────┘
```

### Níveis de Segurança

| Nível | Tipo | Implementação |
|-------|------|---------------|
| **Rede** | Firewall, HTTPS | Infraestrutura |
| **Aplicação** | Rate limiting, CORS | Middleware |
| **Dados** | Validação, Sanitização | Form Requests |
| **Lógica** | Autorização, Transações | Services |
| **Persistência** | Encryption, Backups | Database |

## 🔑 Autenticação e Autorização

### Hash de Senhas

```php
// Hashing seguro com bcrypt (cost factor 10)
'password' => 'hashed', // Cast automático no Model

// Criação manual
use Illuminate\Support\Facades\Hash;

$hashedPassword = Hash::make($password); // bcrypt com salt aleatório
$verified = Hash::check($plainPassword, $hashedPassword);
```

### Configuração de Hashing

**config/hashing.php**
```php
'bcrypt' => [
    'rounds' => env('BCRYPT_ROUNDS', 10), // Aumentar para 12+ em produção
],
```

### Autorização de Operações

```php
// Verificação se lojista pode enviar
if ($payer->isMerchant()) {
    throw new \Exception('Merchants cannot send transfers');
}

// Verificação de saldo
if (!$payer->hasSufficientBalance($amount)) {
    throw new \Exception('Insufficient balance');
}

// Verificação de auto-transferência
if ($payer->id === $payee->id) {
    throw new \Exception('Cannot transfer to yourself');
}
```

## ✅ Validação de Dados

### Form Requests

Todas as entradas são validadas via Form Requests:

#### TransferRequest

```php
public function rules(): array
{
    return [
        'value' => [
            'required',
            'numeric',
            'min:0.01',          // Valor mínimo
            'max:999999.99',     // Valor máximo
            'decimal:2',         // Duas casas decimais
        ],
        'payer' => [
            'required',
            'integer',
            'exists:users,id',   // Usuário existe
            'different:payee',   // Não é o mesmo que payee
        ],
        'payee' => [
            'required',
            'integer',
            'exists:users,id',
        ],
    ];
}
```

#### StoreUserRequest

```php
public function rules(): array
{
    return [
        'name' => [
            'required',
            'string',
            'min:3',
            'max:255',
            'regex:/^[a-zA-ZÀ-ÿ\s]+$/', // Apenas letras e espaços
        ],
        'email' => [
            'required',
            'email:rfc,dns',      // Validação completa de email
            'max:255',
            'unique:users,email', // Unicidade
        ],
        'document' => [
            'required',
            'string',
            'unique:users,document',
            'regex:/^[0-9]{11}$|^[0-9]{14}$/', // CPF ou CNPJ
        ],
        'password' => [
            'required',
            'string',
            'min:8',              // Mínimo 8 caracteres
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', // Complexidade
        ],
        'type' => [
            'required',
            Rule::in(['common', 'merchant']), // Enum validado
        ],
        'balance' => [
            'nullable',
            'numeric',
            'min:0',
            'max:999999999.99',
        ],
    ];
}
```

### Validação Customizada

```php
// Validação de CPF/CNPJ
use App\Rules\ValidDocument;

'document' => ['required', new ValidDocument],

// ValidDocument Rule
public function passes($attribute, $value)
{
    $length = strlen($value);
    
    if ($length === 11) {
        return $this->validateCPF($value);
    } elseif ($length === 14) {
        return $this->validateCNPJ($value);
    }
    
    return false;
}
```

### Sanitização de Entrada

```php
// Middleware de sanitização
class SanitizeInput
{
    public function handle($request, Closure $next)
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Remove tags HTML
                $value = strip_tags($value);
                
                // Remove espaços extras
                $value = trim($value);
                
                // Escape caracteres especiais
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });
        
        $request->merge($input);
        
        return $next($request);
    }
}
```

## 🛡️ Proteção contra Ataques

### SQL Injection

**Proteção**: Uso exclusivo de Eloquent ORM e Query Builder

```php
// ✅ Seguro - Prepared statements automáticos
User::where('email', $email)->first();

// ✅ Seguro - Binding automático
DB::table('users')->where('id', $id)->get();

// ❌ NUNCA fazer
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### XSS (Cross-Site Scripting)

**Proteção**: Sanitização de saída automática

```php
// API Resources escapam automaticamente
return [
    'name' => $this->name, // Escapado na serialização JSON
    'email' => $this->email,
];

// Blade também escapa automaticamente
{{ $user->name }} // Escapado
{!! $user->name !!} // NÃO escapado - evitar!
```

### CSRF (Cross-Site Request Forgery)

**Proteção**: Token CSRF em formulários

```php
// Middleware CSRF habilitado por padrão
// config/app.php
'middleware' => [
    \App\Http\Middleware\VerifyCsrfToken::class,
],

// Exceções para API (stateless)
protected $except = [
    'api/*',
];
```

### Mass Assignment

**Proteção**: Whitelist de campos no Model

```php
class User extends Model
{
    // ✅ Apenas campos permitidos
    protected $fillable = [
        'name',
        'email',
        'document',
        'password',
        'type',
        'balance',
    ];
    
    // Campos protegidos
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];
}
```

### Directory Traversal

**Proteção**: Validação de caminhos

```php
// ❌ Vulnerável
$file = storage_path('files/' . $request->file);

// ✅ Seguro
$file = storage_path('files/' . basename($request->file));

// ✅ Melhor ainda
if (!Str::startsWith(realpath($file), storage_path('files/'))) {
    abort(403);
}
```

## 🔒 Segurança de Transações

### Lock Pessimista

Previne race conditions em operações concorrentes:

```php
DB::transaction(function () use ($payerId, $payeeId, $amount) {
    // Lock pessimista - bloqueia registros até commit
    $payer = User::lockForUpdate()->findOrFail($payerId);
    $payee = User::lockForUpdate()->findOrFail($payeeId);
    
    // Validações e operações...
    $payer->decrement('balance', $amount);
    $payee->increment('balance', $amount);
});
```

### Transações ACID

```php
try {
    DB::beginTransaction();
    
    // Operações atômicas
    $payer->decrement('balance', $amount);
    $payee->increment('balance', $amount);
    $transaction->create([...]);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack(); // Rollback automático em caso de erro
    throw $e;
}
```

### Idempotência

```php
// Uso de transaction_id único para evitar duplicatas
$transaction = Transaction::firstOrCreate(
    ['idempotency_key' => $idempotencyKey],
    ['payer_id' => $payerId, 'payee_id' => $payeeId, 'value' => $amount]
);

if ($transaction->wasRecentlyCreated) {
    // Processar transferência
} else {
    // Retornar transação existente
}
```

## 🔐 Dados Sensíveis

### Ocultação de Campos

```php
class User extends Model
{
    // Campos ocultos em JSON
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

### Criptografia de Dados

```php
// Casts com criptografia automática
protected $casts = [
    'sensitive_data' => 'encrypted',
];

// Uso
$user->sensitive_data = 'valor secreto'; // Criptografado automaticamente
```

### Logs sem Dados Sensíveis

```php
// ❌ NUNCA logar dados sensíveis
Log::info('User created', ['password' => $password]);

// ✅ Logar apenas metadados
Log::info('User created', [
    'user_id' => $user->id,
    'has_password' => !empty($password),
    'password_length' => strlen($password),
]);
```

## 🚦 Rate Limiting

### Configuração Global

**app/Http/Kernel.php**
```php
protected $middlewareGroups = [
    'api' => [
        'throttle:api', // 60 requisições por minuto
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### Rate Limiting Customizado

**routes/api.php**
```php
// Limite diferenciado para transferências
Route::post('/transfer', [TransferController::class, 'transfer'])
    ->middleware('throttle:10,1'); // 10 por minuto

// Limite para autenticação
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 tentativas por minuto
```

### Rate Limiting Dinâmico

```php
// Por usuário
RateLimiter::for('per-user', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(100)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});

// Por endpoint sensível
RateLimiter::for('sensitive', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.'
            ], 429);
        });
});
```

### Headers de Rate Limit

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640000000
Retry-After: 60
```

## 🔍 Auditoria e Compliance

### Logs de Auditoria

```php
// Log de todas as transações
Log::channel('audit')->info('Transfer executed', [
    'transaction_id' => $transaction->id,
    'payer_id' => $payer->id,
    'payee_id' => $payee->id,
    'amount' => $amount,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now()->toIso8601String(),
]);
```

### Rastreabilidade

```php
// Request ID único em todas as operações
$requestId = Str::uuid();

Log::withContext(['request_id' => $requestId])->info('Processing...');

// Incluir em responses
return response()->json($data)->header('X-Request-ID', $requestId);
```

### Retenção de Dados

```sql
-- Tabela de auditoria
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(255) NOT NULL,
    model_type VARCHAR(255) NULL,
    model_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
```

### LGPD/GDPR Compliance

```php
// Anonimização de dados
public function anonymize()
{
    $this->update([
        'name' => 'Usuário Anônimo',
        'email' => 'anonimo_' . $this->id . '@example.com',
        'document' => str_repeat('*', strlen($this->document)),
    ]);
}

// Exportação de dados
public function exportData()
{
    return [
        'personal_data' => [
            'name' => $this->name,
            'email' => $this->email,
            'document' => $this->document,
        ],
        'transactions' => $this->transactions()->get(),
        'created_at' => $this->created_at,
    ];
}
```

## 🔐 Configurações de Segurança

### Variáveis de Ambiente

```bash
# Produção
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... # Gerar com: php artisan key:generate

# HTTPS obrigatório
APP_URL=https://api.example.com
FORCE_HTTPS=true

# Sessão segura
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simple_payment
DB_USERNAME=app_user
DB_PASSWORD=strong_password_here

# Desabilitar verificação SSL apenas em DEV
AUTHORIZER_VERIFY_SSL=true
NOTIFIER_VERIFY_SSL=true
```

### Headers de Segurança

**app/Http/Middleware/SecurityHeaders.php**
```php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    return $response
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('X-Frame-Options', 'DENY')
        ->header('X-XSS-Protection', '1; mode=block')
        ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->header('Content-Security-Policy', "default-src 'self'")
        ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
}
```

### CORS

**config/cors.php**
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Request-ID'],
    'exposed_headers' => ['X-Request-ID'],
    'max_age' => 3600,
    'supports_credentials' => true,
];
```

## 🔒 Checklist de Segurança

### Desenvolvimento
- [x] Form Requests para todas as entradas
- [x] Sanitização de dados
- [x] Validação de tipos e limites
- [x] Proteção contra SQL Injection (ORM)
- [x] Proteção contra XSS
- [x] Mass assignment protection
- [x] Transações ACID
- [x] Lock pessimista

### Infraestrutura
- [ ] HTTPS obrigatório
- [ ] Firewall configurado
- [ ] Rate limiting ativo
- [ ] Headers de segurança
- [ ] CORS configurado
- [ ] Backups automáticos
- [ ] Monitoramento de segurança

### Dados
- [x] Senhas hasheadas (bcrypt)
- [x] Campos sensíveis ocultos
- [ ] Criptografia em repouso
- [ ] Criptografia em trânsito
- [x] Logs sem dados sensíveis
- [ ] Anonimização de dados

### Compliance
- [x] Logs de auditoria
- [x] Rastreabilidade de operações
- [ ] Política de privacidade
- [ ] Termos de uso
- [ ] Exportação de dados (LGPD)
- [ ] Direito ao esquecimento

## 🚨 Incident Response

### Detecção de Anomalias

```php
// Monitorar tentativas de transferências suspeitas
if ($amount > 10000) {
    Log::warning('High value transfer', [
        'amount' => $amount,
        'payer_id' => $payerId,
        'payee_id' => $payeeId,
    ]);
    
    // Notificar equipe de segurança
    // event(new HighValueTransferAttempted(...));
}

// Monitorar múltiplas falhas
if ($failedAttempts >= 5) {
    Log::alert('Multiple failed attempts', [
        'user_id' => $userId,
        'ip' => $request->ip(),
    ]);
    
    // Bloquear temporariamente
    Cache::put("blocked:user:{$userId}", true, now()->addHours(1));
}
```

### Bloqueio de IP

```php
// Middleware de bloqueio
class BlockSuspiciousIPs
{
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        
        if (Cache::has("blocked:ip:{$ip}")) {
            return response()->json([
                'message' => 'Access denied'
            ], 403);
        }
        
        return $next($request);
    }
}
```

## 📚 Referências

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/11.x/security)
- [PCI DSS Compliance](https://www.pcisecuritystandards.org/)
- [LGPD - Lei Geral de Proteção de Dados](http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm)

---

📚 **Ver também**:
- [Observabilidade](OBSERVABILIDADE.md)
- [Arquitetura](ARQUITETURA.md)
- [API](API.md)
