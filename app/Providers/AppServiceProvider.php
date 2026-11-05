<?php

namespace App\Providers;

use App\Http\Middleware\VerifyServiceKey;
use App\Models\Article;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Industry;
use App\Models\Lead;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Solution;
use App\Models\User;
use App\Observers\CommercialRecordObserver;
use App\Observers\ContentObserver;
use App\Observers\UserObserver;
use App\Policies\RolePolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        $this->registerObservers();

        Gate::policy(Role::class, RolePolicy::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }

    /**
     * Auditoría, historial de estados y revalidación del frontend.
     */
    private function registerObservers(): void
    {
        foreach ([Product::class, Service::class, Solution::class, Industry::class, Category::class, Article::class] as $model) {
            $model::observe(ContentObserver::class);
        }

        foreach ([Lead::class, ContactMessage::class, QuoteRequest::class, DemoRequest::class, DiagnosticRequest::class] as $model) {
            $model::observe(CommercialRecordObserver::class);
        }

        User::observe(UserObserver::class);
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
