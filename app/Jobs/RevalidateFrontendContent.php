<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pide a Next.js invalidar las etiquetas de caché del contenido modificado.
 *
 * Firma: `X-Revalidate-Signature: sha256=HMAC(timestamp + "." + cuerpo)` con
 * `FRONTEND_REVALIDATE_SECRET`, y `X-Revalidate-Timestamp` para que Next.js
 * rechace firmas viejas (repetición). Sin URL o secreto configurados, no hace
 * nada. Se ejecuta en cola después de confirmar la transacción.
 */
class RevalidateFrontendContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    /**
     * @param  list<string>  $tags
     */
    public function __construct(public array $tags)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $url = (string) config('services.frontend.revalidate_url');
        $secret = (string) config('services.frontend.revalidate_secret');

        if ($url === '' || $secret === '' || $this->tags === []) {
            return;
        }

        $body = json_encode(['tags' => array_values(array_unique($this->tags))], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->getTimestamp();

        $response = Http::timeout(5)
            ->acceptJson()
            ->withHeaders([
                'X-Revalidate-Timestamp' => $timestamp,
                'X-Revalidate-Signature' => 'sha256='.self::sign($timestamp, $body, $secret),
            ])
            ->withBody($body, 'application/json')
            ->post($url);

        if ($response->failed()) {
            Log::warning('No fue posible revalidar el frontend.', ['status' => $response->status(), 'tags' => $this->tags]);
            $response->throw();
        }
    }

    public static function sign(string $timestamp, string $body, string $secret): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }
}
