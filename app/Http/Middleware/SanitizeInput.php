<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitizar input apenas se não for arquivo
        if (!$request->hasFile('file')) {
            $input = $this->sanitize($request->all());
            $request->merge($input);
        }

        return $next($request);
    }

    /**
     * Sanitizar dados recursivamente
     */
    private function sanitize(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                // Remove espaços no início e fim
                $value = trim($value);
                
                // Remove tags HTML (mantém entidades HTML)
                $value = strip_tags($value);
                
                // Escape caracteres especiais para prevenir XSS
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
                
                // Remove caracteres de controle (exceto tabs, newlines e returns)
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            }
        });

        return $data;
    }
}
