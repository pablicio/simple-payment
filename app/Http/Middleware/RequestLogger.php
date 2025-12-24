<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Gerar ou obter Request ID
        $requestId = $request->header('X-Request-ID') 
            ?? Str::uuid()->toString();
        
        $request->headers->set('X-Request-ID', $requestId);

        // Log da requisição
        Log::info('HTTP Request received', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'input_keys' => array_keys($request->except(['password', 'password_confirmation'])),
        ]);

        // Processar requisição
        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000;

        // Adicionar Request ID no header da resposta
        $response->headers->set('X-Request-ID', $requestId);

        // Log da resposta
        $logLevel = $response->status() >= 500 ? 'error' 
            : ($response->status() >= 400 ? 'warning' : 'info');

        Log::log($logLevel, 'HTTP Response sent', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->status(),
            'duration_ms' => round($duration, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);

        return $response;
    }
}
