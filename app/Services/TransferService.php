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
        return DB::transaction(function () use ($payerId, $payeeId, $amount) {
            
            // 1. Buscar usuários com lock pessimista
            $payer = User::lockForUpdate()->findOrFail($payerId);
            $payee = User::lockForUpdate()->findOrFail($payeeId);
            
            // 2. Validar regras de negócio
            $this->validateTransfer($payer, $payee, $amount);
            
            // 3. Consultar autorizador externo
            if (!$this->authorize()) {
                throw new \Exception('Transfer not authorized');
            }
            
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
            
            // 6. Marcar como completa
            $transaction->markAsCompleted();
            
            // 7. Invalidar cache após transferência bem-sucedida
            $this->invalidateCache($payerId, $payeeId, $transaction->id);
            
            // 8. Notificar de forma assíncrona (não bloqueia - usa queue)
            try {
                $this->notificationService->notifyTransferReceived($transaction);
            } catch (\Exception $e) {
                // Log mas não quebra a transferência
                Log::warning('Failed to queue notification', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $transaction->load('payer', 'payee');
        });
    }

    /**
     * Validar regras de negócio da transferência
     */
    private function validateTransfer(User $payer, User $payee, float $amount): void
    {
        // Lojista não pode enviar
        if ($payer->isMerchant()) {
            throw new \Exception('Merchants cannot send transfers');
        }
        
        // Não pode transferir para si mesmo
        if ($payer->id === $payee->id) {
            throw new \Exception('Cannot transfer to yourself');
        }
        
        // Valor deve ser positivo
        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than zero');
        }
        
        // Verificar saldo suficiente
        if (!$payer->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }
    }

    /**
     * Consultar serviço autorizador externo
     */
    private function authorize(): bool
    {
        // Modo de teste: sempre autoriza se configurado
        if (config('transfer.authorizer_mock', false)) {
            Log::info('Transfer authorized by mock');
            return true;
        }

        try {
            // Configurar HTTP client
            $client = Http::timeout(5);
            
            // Desabilitar verificação SSL em desenvolvimento se configurado
            if (config('transfer.authorizer_verify_ssl', true) === false) {
                $client = $client->withOptions(['verify' => false]);
            }
            
            $response = $client->get(config('transfer.authorizer_url'));
            
            // Verifica se a resposta foi bem sucedida (status 2xx)
            if (!$response->successful()) {
                Log::warning('Authorizer returned non-successful status', [
                    'status' => $response->status()
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
            
            Log::info('Authorizer response', [
                'data' => $data,
                'authorized' => $authorized
            ]);
            
            return $authorized;
            
        } catch (\Exception $e) {
            Log::warning('Authorizer service failed', [
                'error' => $e->getMessage(),
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
        // Cache de usuários
        Cache::forget("user:{$payerId}");
        Cache::forget("user:{$payeeId}");
        Cache::forget("user:{$payerId}:balance");
        Cache::forget("user:{$payeeId}:balance");
        Cache::forget('users:all');

        // Cache de transações
        Cache::forget("transaction:{$transactionId}");
        Cache::forget("transaction:user:{$payerId}:stats");
        Cache::forget("transaction:user:{$payeeId}:stats");
        
        // Nota: Cache das listagens de transações expira naturalmente (TTL de 5 minutos)
        // Para invalidação imediata de todas as listagens, seria necessário:
        // 1. Usar Redis/Memcached (que suportam tags)
        // 2. Rastrear manualmente todas as chaves de listagem
        // 3. Usar um prefixo de versão global
    }
}
