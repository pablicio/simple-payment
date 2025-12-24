<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de tentativas
     */
    public $tries = 3;

    /**
     * Tempo de espera entre tentativas (em segundos)
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Timeout do job em segundos
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Notification $notification
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $attemptNumber = $this->notification->attempts + 1;

        Log::info('Notification job started', [
            'notification_id' => $this->notification->id,
            'transaction_id' => $this->notification->transaction_id,
            'user_id' => $this->notification->user_id,
            'attempt' => $attemptNumber,
            'max_attempts' => $this->tries,
        ]);

        try {
            // Enviar notificação via API externa
            $response = $this->sendToExternalApi();

            $duration = (microtime(true) - $startTime) * 1000;

            // Marcar como enviada
            $this->notification->markAsSent(
                json_encode($response)
            );

            Log::info('Notification sent successfully', [
                'notification_id' => $this->notification->id,
                'transaction_id' => $this->notification->transaction_id,
                'attempt' => $attemptNumber,
                'duration_ms' => round($duration, 2),
            ]);

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->handleFailure($e, $duration);
        }
    }

    /**
     * Enviar notificação para API externa
     */
    private function sendToExternalApi(): array
    {
        $url = config('transfer.notifier_url');
        
        // Preparar payload de acordo com a API
        $payload = [
            'email' => $this->notification->user->email,
            'message' => $this->notification->message,
        ];

        Log::debug('Sending notification to external API', [
            'notification_id' => $this->notification->id,
            'url' => $url,
            'payload' => $payload,
        ]);

        // Configurar HTTP client
        $client = Http::timeout(10)
            ->retry(2, 1000); // 2 tentativas com 1 segundo de intervalo
        
        // Desabilitar verificação SSL em desenvolvimento se configurado
        if (config('transfer.notifier_verify_ssl', true) === false) {
            $client = $client->withOptions(['verify' => false]);
        }

        $startTime = microtime(true);

        // Fazer requisição POST
        $response = $client->post($url, $payload);

        $responseTime = (microtime(true) - $startTime) * 1000;

        Log::debug('External API response received', [
            'notification_id' => $this->notification->id,
            'status_code' => $response->status(),
            'response_time_ms' => round($responseTime, 2),
        ]);

        // Verificar se foi bem-sucedido
        if (!$response->successful()) {
            throw new \Exception(
                "Notifier API returned status {$response->status()}: " . 
                $response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Tratar falha no envio
     */
    private function handleFailure(\Exception $e, float $duration): void
    {
        $errorMessage = $e->getMessage();
        $attemptNumber = $this->attempts();
        
        // Verificar se é a última tentativa
        $isFinalAttempt = $attemptNumber >= $this->tries;

        // Registrar falha
        $this->notification->markAsFailed($errorMessage, $isFinalAttempt);

        $logLevel = $isFinalAttempt ? 'error' : 'warning';

        Log::log($logLevel, 'Notification sending failed', [
            'notification_id' => $this->notification->id,
            'transaction_id' => $this->notification->transaction_id,
            'user_id' => $this->notification->user_id,
            'attempt' => $attemptNumber,
            'max_attempts' => $this->tries,
            'final_attempt' => $isFinalAttempt,
            'error' => $errorMessage,
            'error_type' => get_class($e),
            'duration_ms' => round($duration, 2),
            'next_retry_in_seconds' => !$isFinalAttempt ? $this->backoff[$attemptNumber - 1] ?? 0 : null,
        ]);

        // Se não é a última tentativa, lançar exceção para retry
        if (!$isFinalAttempt) {
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'transaction_id' => $this->notification->transaction_id,
            'user_id' => $this->notification->user_id,
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'total_attempts' => $this->tries,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Marcar como falha definitiva se ainda não foi
        if ($this->notification->status !== Notification::STATUS_FAILED) {
            $this->notification->markAsFailed(
                $exception->getMessage(), 
                true
            );
        }
    }
}
