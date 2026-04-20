<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove server-level leak from PHP runtime
        @header_remove('X-Powered-By');
        @header_remove('Server');
        @ini_set('expose_php', 'Off');

        // Ensure we're working with a Response object
        if ($response instanceof \Illuminate\Http\Response || $response instanceof Response) {
            // Anti-clickjacking headers
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            
            // Content-type security
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            
            // Security policies
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Cache control (prevent information leakage)
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            
            // Content Security Policy
            $cspDirectives = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "img-src 'self' data:",
                "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "connect-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                'block-all-mixed-content',
            ];

            if (config('app.debug') || app()->environment('local')) {
                $devOrigins = [
                    'http://127.0.0.1:5173',
                    'http://localhost:5173',
                ];

                $httpDevOrigins = implode(' ', $devOrigins);
                $wsDevOrigins = implode(' ', array_map(fn ($origin) => preg_replace('#^http#', 'ws', $origin), $devOrigins));

                $cspDirectives[1] .= ' ' . $httpDevOrigins;
                $cspDirectives[2] .= ' ' . $httpDevOrigins;
                $cspDirectives[5] .= ' ' . $httpDevOrigins . ' ' . $wsDevOrigins;
            }

            $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives) . ';');

            // HSTS (Strict-Transport-Security) - always set
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
