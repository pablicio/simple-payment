<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TransferService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Executar transferência entre usuários
     */
    public function transfer(int $payerId, int $payeeId, float $amount): Transaction
    {
        $startTime = microtime(true);
        $requestId = request()->header('X-Request-ID', \Illuminate\Support\Str::uuid());
        
        // Log estruturado de início
        Log::info('Transfer initiated', [
            'request_id' => $requestId,
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'amount' => $amount,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        try {
            $transaction = DB::transaction(function () use ($payerId, $payeeId, $amount, $requestId) {
                
                // 1. Buscar usuários com lock pessimista
                $payer = User::lockForUpdate()->findOrFail($payerId);
                $payee = User::lockForUpdate()->findOrFail($payeeId);
                
                Log::debug('Users locked for update', [
                    'request_id' => $requestId,
                    'payer_id' => $payer->id,
                    'payee_id' => $payee->id,
                ]);
                
                // 2. Validar regras de negócio
                $this->validateTransfer($payer, $payee, $amount, $requestId);
                
                Log::info('Transfer validation passed', [
                    'request_id' => $requestId,
                    'payer_balance' => $payer->balance,
                ]);
                
                // 3. Consultar autorizador externo
                $authStartTime = microtime(true);
                if (!$this->authorize($requestId)) {
                    throw new \Exception('Transfer not authorized');
                }
                $authDuration = (microtime(true) - $authStartTime) * 1000;
                
                Log::info('External authorizer approved', [
                    'request_id' => $requestId,
                    'duration_ms' => round($authDuration, 2),
                ]);
                
                // 4. Criar transação com status pending
                $transaction = Transaction::create([
                    'payer_id' => $payer->id,
                    'payee_id' => $payee->id,
                    'value' => $amount,
                    'status' => Transaction::STATUS_PENDING,
                ]);
                
                // 5. Executar transferência
                $payer->decrement('balance', $amount);
                $payee->increment('balance', $amount);
                
                Log::info('Balances updated', [
                    'request_id' => $requestId,
                    'transaction_id' => $transaction->id,
                    'payer_new_balance' => $payer->fresh()->balance,
                    'payee_new_balance' => $payee->fresh()->balance,
                ]);
                
                // 6. Marcar como completa
                $transaction->markAsCompleted();
                
                // 7. Invalidar cache após transferência bem-sucedida
                $this->invalidateCache($payerId, $payeeId, $transaction->id);
                
                // 8. Notificar de forma assíncrona (não bloqueia - usa queue)
                try {
                    $this->notificationService->notifyTransferReceived($transaction);
                    
                    Log::info('Notification dispatched', [
                        'request_id' => $requestId,
                        'transaction_id' => $transaction->id,
                    ]);
                } catch (\Exception $e) {
                    // Log mas não quebra a transferência
                    Log::warning('Failed to queue notification', [
                        'request_id' => $requestId,
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                return $transaction->load('payer', 'payee');
            });

            $duration = (microtime(true) - $startTime) * 1000;
            
            // Log de sucesso com métricas
            Log::info('Transfer completed successfully', [
                'request_id' => $requestId,
                'transaction_id' => $transaction->id,
                'duration_ms' => round($duration, 2),
                'cache_invalidated' => true,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Log estruturado de erro
            Log::error('Transfer failed', [
                'request_id' => $requestId,
                'payer_id' => $payerId,
                'payee_id' => $payeeId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'duration_ms' => round($duration, 2),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validar regras de negócio da transferência
     */
    private function validateTransfer(User $payer, User $payee, float $amount, string $requestId): void
    {
        // Lojista não pode enviar
        if ($payer->isMerchant()) {
            Log::warning('Merchant attempted to send transfer', [
                'request_id' => $requestId,
                'payer_id' => $payer->id,
            ]);
            throw new \Exception('Merchants cannot send transfers');
        }
        
        // Não pode transferir para si mesmo
        if ($payer->id === $payee->id) {
            Log::warning('Self-transfer attempted', [
                'request_id' => $requestId,
                'user_id' => $payer->id,
            ]);
            throw new \Exception('Cannot transfer to yourself');
        }
        
        // Valor deve ser positivo
        if ($amount <= 0) {
            Log::warning('Invalid transfer amount', [
                'request_id' => $requestId,
                'amount' => $amount,
            ]);
            throw new \Exception('Amount must be greater than zero');
        }
        
        // Verificar saldo suficiente
        if (!$payer->hasSufficientBalance($amount)) {
            Log::warning('Insufficient balance', [
                'request_id' => $requestId,
                'payer_id' => $payer->id,
                'required_amount' => $amount,
                'current_balance' => $payer->balance,
            ]);
            throw new \Exception('Insufficient balance');
        }

        // Detectar valores suspeitos (acima de 10.000)
        if ($amount > 10000) {
            Log::alert('High value transfer detected', [
                'request_id' => $requestId,
                'payer_id' => $payer->id,
                'payee_id' => $payee->id,
                'amount' => $amount,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Consultar serviço autorizador externo
     */
    private function authorize(string $requestId): bool
    {
        // Modo de teste: sempre autoriza se configurado
        if (config('transfer.authorizer_mock', false)) {
            Log::info('Transfer authorized by mock', [
                'request_id' => $requestId,
            ]);
            return true;
        }

        $authorizerUrl = config('transfer.authorizer_url');
        
        Log::info('Authorizer request sent', [
            'request_id' => $requestId,
            'url' => $authorizerUrl,
        ]);

        try {
            // Configurar HTTP client com timeout e retry
            $client = Http::timeout(5)
                ->retry(2, 100); // 2 tentativas com 100ms entre elas
            
            // Desabilitar verificação SSL em desenvolvimento se configurado
            if (config('transfer.authorizer_verify_ssl', true) === false) {
                $client = $client->withOptions(['verify' => false]);
            }
            
            $startTime = microtime(true);
            $response = $client->get($authorizerUrl);
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            // Verifica se a resposta foi bem sucedida (status 2xx)
            if (!$response->successful()) {
                Log::warning('Authorizer returned non-successful status', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'response_time_ms' => round($responseTime, 2),
                ]);
                return false;
            }
            
            // Verifica o conteúdo da resposta
            $data = $response->json();
            
            // API retorna: {"status": "fail"} ou {"status": "success"}
            // Ou retorna: {"message": "Autorizado"}
            if (isset($data['status'])) {
                $authorized = $data['status'] === 'success';
            } elseif (isset($data['message'])) {
                $authorized = stripos($data['message'], 'autorizado') !== false;
            } else {
                $authorized = true; // Se não tem status/message, autoriza
            }
            
            Log::info('Authorizer response received', [
                'request_id' => $requestId,
                'authorized' => $authorized,
                'response_time_ms' => round($responseTime, 2),
                'status_code' => $response->status(),
            ]);
            
            return $authorized;
            
        } catch (\Exception $e) {
            Log::error('Authorizer service failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'fallback' => 'denying'
            ]);
            
            // Em caso de erro, nega a transferência por segurança
            return false;
        }
    }

    /**
     * Invalidar cache após transferência
     * 
     * Limpa cache de:
     * - Usuários envolvidos
     * - Saldos dos usuários
     * - Transações
     * - Estatísticas
     */
    private function invalidateCache(int $payerId, int $payeeId, int $transactionId): void
    {
        $invalidatedKeys = [];

        // Cache de usuários
        $userKeys = [
            "user:{$payerId}",
            "user:{$payeeId}",
            "user:{$payerId}:balance",
            "user:{$payeeId}:balance",
            'users:all'
        ];

        foreach ($userKeys as $key) {
            if (Cache::forget($key)) {
                $invalidatedKeys[] = $key;
            }
        }

        // Cache de transações
        $transactionKeys = [
            "transaction:{$transactionId}",
            "transaction:user:{$payerId}:stats",
            "transaction:user:{$payeeId}:stats",
        ];

        foreach ($transactionKeys as $key) {
            if (Cache::forget($key)) {
                $invalidatedKeys[] = $key;
            }
        }
        
        Log::debug('Cache invalidated', [
            'keys' => $invalidatedKeys,
            'count' => count($invalidatedKeys),
        ]);
    }
}
