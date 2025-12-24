<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Previne que o navegador faça MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Previne que a página seja exibida em um iframe
        $response->headers->set('X-Frame-Options', 'DENY');

        // Habilita proteção XSS do navegador
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Força HTTPS por 1 ano
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'"
        );

        // Controla quanto de informação do Referer é enviado
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Controla quais recursos do navegador podem ser acessados
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=()'
        );

        return $response;
    }
}
