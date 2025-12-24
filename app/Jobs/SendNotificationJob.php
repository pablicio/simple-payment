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
        Log::info('Sending notification', [
            'notification_id' => $this->notification->id,
            'attempt' => $this->notification->attempts + 1,
        ]);

        try {
            // Enviar notificação via API externa
            $response = $this->sendToExternalApi();

            // Marcar como enviada
            $this->notification->markAsSent(
                json_encode($response)
            );

            Log::info('Notification sent successfully', [
                'notification_id' => $this->notification->id,
            ]);

        } catch (\Exception $e) {
            $this->handleFailure($e);
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

        // Configurar HTTP client
        $client = Http::timeout(10)
            ->retry(2, 1000); // 2 tentativas com 1 segundo de intervalo
        
        // Desabilitar verificação SSL em desenvolvimento se configurado
        if (config('transfer.notifier_verify_ssl', true) === false) {
            $client = $client->withOptions(['verify' => false]);
        }

        // Fazer requisição POST
        $response = $client->post($url, $payload);

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
    private function handleFailure(\Exception $e): void
    {
        $errorMessage = $e->getMessage();
        
        // Verificar se é a última tentativa
        $isFinalAttempt = $this->attempts() >= $this->tries;

        // Registrar falha
        $this->notification->markAsFailed($errorMessage, $isFinalAttempt);

        Log::warning('Notification failed', [
            'notification_id' => $this->notification->id,
            'attempt' => $this->attempts(),
            'final_attempt' => $isFinalAttempt,
            'error' => $errorMessage,
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
            'error' => $exception->getMessage(),
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
