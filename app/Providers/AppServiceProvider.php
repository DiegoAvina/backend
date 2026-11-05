<?php

namespace App\Providers;

use App\Http\Middleware\VerifyServiceKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->configureRateLimiting();
    }

    /**
     * Límites de la API v1.
     *
     * - `content-api`: lectura pública, por IP de quien consulta.
     * - `lead-submissions`: formularios, por IP real del visitante y por correo,
     *   para frenar envíos masivos aunque roten la IP o el correo.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('content-api', fn (Request $request): Limit => Limit::perMinute(
            (int) config('services.frontend.content_rate_limit', 300),
        )->by('content:'.$request->ip()));

        RateLimiter::for('lead-submissions', function (Request $request): array {
            $ip = (string) ($request->attributes->get(VerifyServiceKey::CLIENT_IP) ?? $request->ip());
            $email = mb_strtolower(trim((string) $request->input('email', '')));

            $limits = [
                Limit::perMinute(5)->by('leads:ip-minute:'.$ip),
                Limit::perHour(30)->by('leads:ip-hour:'.$ip),
            ];

            if ($email !== '') {
                $limits[] = Limit::perHour(10)->by('leads:email:'.hash('sha256', $email));
            }

            return $limits;
        });
    }
}
