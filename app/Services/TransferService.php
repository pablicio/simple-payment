<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransferService
{
    const AUTHORIZER_URL = 'https://util.devi.tools/api/v2/authorize';
    const NOTIFIER_URL = 'https://util.devi.tools/api/v1/notify';

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
            
            // 7. Notificar (não bloqueia se falhar)
            $this->notifyPayee($payee, $payer, $amount);
            
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
        if (env('TRANSFER_AUTHORIZER_MOCK', false)) {
            \Log::info('Transfer authorized by mock');
            return true;
        }

        try {
            $response = Http::timeout(5)->get(self::AUTHORIZER_URL);
            
            // Verifica se a resposta foi bem sucedida (status 2xx)
            if (!$response->successful()) {
                \Log::warning('Authorizer returned non-successful status', [
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
            
            \Log::info('Authorizer response', [
                'data' => $data,
                'authorized' => $authorized
            ]);
            
            return $authorized;
            
        } catch (\Exception $e) {
            \Log::warning('Authorizer service failed', [
                'error' => $e->getMessage(),
                'fallback' => 'denying'
            ]);
            
            // Em caso de erro, nega a transferência por segurança
            return false;
        }
    }

    /**
     * Notificar o recebedor (assíncrono, não bloqueia)
     */
    private function notifyPayee(User $payee, User $payer, float $amount): void
    {
        try {
            Http::timeout(3)->post(self::NOTIFIER_URL, [
                'email' => $payee->email,
                'message' => "Você recebeu R$ {$amount} de {$payer->name}",
            ]);
        } catch (\Exception $e) {
            // Log mas não quebra a transferência
            \Log::warning('Notification failed', [
                'payee_id' => $payee->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
