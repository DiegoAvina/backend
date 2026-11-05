<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege los endpoints de escritura: solo el servidor de Next.js, que conoce
 * la llave de servicio, puede enviar solicitudes comerciales.
 *
 * Con la llave válida se confía en `X-Client-IP` y `X-Client-User-Agent`,
 * que el servidor de Next.js toma de la petición original del visitante.
 * Sin llave configurada en el servidor, todas las escrituras se rechazan.
 */
class VerifyServiceKey
{
    public const CLIENT_IP = 'client_ip';

    public const CLIENT_USER_AGENT = 'client_user_agent';

    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.frontend.service_key');
        $provided = (string) $request->header('X-Service-Key', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return ApiResponse::error('No autorizado.', 401);
        }

        $request->attributes->set(self::CLIENT_IP, $this->clientIp($request));
        $request->attributes->set(self::CLIENT_USER_AGENT, $this->clientUserAgent($request));

        return $next($request);
    }

    private function clientIp(Request $request): ?string
    {
        $forwarded = trim((string) $request->header('X-Client-IP', ''));

        if (filter_var($forwarded, FILTER_VALIDATE_IP) !== false) {
            return $forwarded;
        }

        return $request->ip();
    }

    private function clientUserAgent(Request $request): ?string
    {
        $userAgent = (string) ($request->header('X-Client-User-Agent') ?? '');
        $userAgent = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', $userAgent));

        return $userAgent === '' ? null : mb_substr($userAgent, 0, 500);
    }
}
