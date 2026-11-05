<?php

use App\Enums\LeadSource;
use App\Enums\RequestStatus;
use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Filament\Widgets\CommercialStatsOverview;
use App\Filament\Widgets\ContentStatsOverview;
use App\Filament\Widgets\FollowUpWidget;
use App\Filament\Widgets\LatestRequestsWidget;
use App\Filament\Widgets\LeadSourcesChart;
use App\Filament\Widgets\LeadsPerMonthChart;
use App\Filament\Widgets\LeadStatusChart;
use App\Filament\Widgets\PendingLeadsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\RequestsByTypeChart;
use App\Jobs\RevalidateFrontendContent;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\DemoRequest;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\Dashboard\DashboardMetrics;
use App\Services\Settings\SiteSettings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

describe('site settings', function () {
    beforeEach(fn () => (new SettingsSeeder)->run());

    it('seeds every key empty, without inventing data', function () {
        expect(app(SiteSettings::class)->all())->each->toBeNull();
    });

    it('saves settings from the panel and audits the change', function () {
        actingAsRole(UserRole::Admin);
        Queue::fake();

        Livewire::test(ManageSettings::class)
            ->fillForm(['brand_name' => 'Mi Empresa', 'contact_email' => 'ventas@example.com'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = app(SiteSettings::class);

        expect($settings->get(SettingKey::BrandName))->toBe('Mi Empresa')
            ->and($settings->get(SettingKey::ContactEmail))->toBe('ventas@example.com')
            ->and(AuditLog::query()->where('event', 'settings_updated')->sole()->new_values)
            ->toBe(['brand_name' => 'Mi Empresa', 'contact_email' => 'ventas@example.com']);

        Queue::assertPushed(RevalidateFrontendContent::class, fn ($job) => $job->tags === ['settings']);
    });

    it('validates formats', function () {
        actingAsRole(UserRole::Admin);

        Livewire::test(ManageSettings::class)
            ->fillForm(['contact_email' => 'no-es-correo', 'social_linkedin' => 'http://inseguro.example', 'whatsapp_number' => '+52 55'])
            ->call('save')
            ->assertHasFormErrors(['contact_email', 'social_linkedin', 'whatsapp_number']);
    });

    it('never puts private values in the audit log', function () {
        actingAsRole(UserRole::Admin);

        app(SiteSettings::class)->update(['notification_recipients' => 'ventas@example.com']);

        $log = AuditLog::query()->where('event', 'settings_updated')->sole();
        expect($log->new_values)->toBe(['notification_recipients' => '[privado]']);
    });

    it('denies the settings page to roles without permission', function (UserRole $role) {
        actingAsRole($role);

        $this->get(ManageSettings::getUrl())->assertForbidden();
    })->with([UserRole::Sales, UserRole::Editor, UserRole::Viewer]);

    it('deletes the previous logo when it is replaced', function () {
        Storage::fake('public');
        Storage::disk('public')->put('branding/anterior.png', 'x');
        $settings = app(SiteSettings::class);
        $settings->update(['brand_logo' => 'branding/anterior.png']);

        $settings->update(['brand_logo' => 'branding/nuevo.png']);

        Storage::disk('public')->assertMissing('branding/anterior.png');
    });
});

describe('public settings endpoint', function () {
    beforeEach(fn () => (new SettingsSeeder)->run());

    it('returns only public keys, with empty values as null', function () {
        app(SiteSettings::class)->update([
            'brand_name' => 'Mi Empresa',
            'notification_recipients' => 'interno@example.com',
        ]);

        $response = $this->getJson('/api/v1/settings/public')->assertOk()->assertJsonPath('success', true);

        expect($response->json('data'))
            ->toHaveKey('brand_name', 'Mi Empresa')
            ->toHaveKey('contact_phone', null)
            ->not->toHaveKey('notification_recipients')
            ->and($response->getContent())->not->toContain('interno@example.com');
    });

    it('returns images as absolute public URLs', function () {
        Storage::fake('public');
        app(SiteSettings::class)->update(['brand_logo' => 'branding/logo.png']);

        expect($this->getJson('/api/v1/settings/public')->json('data.brand_logo'))
            ->toBe(Storage::disk('public')->url('branding/logo.png'));
    });
});

describe('dashboard', function () {
    it('computes indicators from the database', function () {
        Lead::factory()->count(2)->create();
        $converted = Lead::factory()->create();
        $converted->forceFill(['status' => RequestStatus::Converted])->save();
        QuoteRequest::factory()->count(3)->create();
        DemoRequest::factory()->create();
        Product::factory()->unpublished()->create();
        Article::factory()->create();
        Article::factory()->draft()->create();

        $metrics = app(DashboardMetrics::class);
        $leadsCount = Lead::query()->count();

        expect($metrics->commercialKpis())->toBe([
            'leads' => $leadsCount,
            'new_leads' => $leadsCount - 1,
            'quotes' => 3,
            'demos' => 1,
            'diagnostics' => 0,
            'converted' => 1,
        ])
            ->and($metrics->contentKpis()['published_products'])->toBe(Product::query()->published()->count())
            ->and($metrics->contentKpis()['published_articles'])->toBe(1)
            ->and($metrics->requestsByType())->toBe(['Cotización' => 3, 'Demo' => 1, 'Diagnóstico' => 0, 'Contacto' => 0])
            ->and($metrics->leadStatusDistribution()['Convertido'])->toBe(1)
            ->and(array_sum($metrics->leadSources()))->toBe($leadsCount);
    });

    it('groups leads per month including empty months', function () {
        Lead::factory()->create(['created_at' => now()->subMonths(2)]);
        Lead::factory()->count(2)->create();

        $series = app(DashboardMetrics::class)->leadsPerMonth(3);

        expect(array_values($series))->toBe([1, 0, 2])
            ->and(array_key_last($series))->toBe(now()->format('Y-m'));
    });

    it('lists requests that need follow-up', function () {
        $stale = QuoteRequest::factory()->create(['created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)]);
        $fresh = QuoteRequest::factory()->create();
        $closed = QuoteRequest::factory()->create(['created_at' => now()->subDays(5)]);
        $closed->forceFill(['status' => RequestStatus::Discarded, 'updated_at' => now()->subDays(5)])->saveQuietly();

        $keys = array_keys(app(DashboardMetrics::class)->requestsNeedingFollowUp());

        expect($keys)->toBe(['QuoteRequest-'.$stale->id]);
    });

    it('shows commercial widgets only to roles with commercial dashboard permission', function (UserRole $role, bool $commercial, bool $content) {
        actingAsRole($role);

        expect(CommercialStatsOverview::canView())->toBe($commercial)
            ->and(LatestRequestsWidget::canView())->toBe($commercial)
            ->and(ContentStatsOverview::canView())->toBe($content);

        $this->get('/admin')->assertOk();
    })->with([
        'admin' => [UserRole::Admin, true, true],
        'sales' => [UserRole::Sales, true, false],
        'editor' => [UserRole::Editor, false, true],
        'viewer' => [UserRole::Viewer, true, true],
    ]);

    it('renders the latest requests widget', function () {
        actingAsRole(UserRole::Sales);
        QuoteRequest::factory()->for(Lead::factory()->state(['name' => 'Cliente Reciente', 'source' => LeadSource::Quote]))->create();

        Livewire::test(LatestRequestsWidget::class)->assertSee('Cliente Reciente');
    });
});

it('renders every dashboard widget with real data', function (string $widget) {
    actingAsRole(UserRole::Admin);
    $lead = Lead::factory()->create(['name' => 'Prospecto Visible']);
    QuoteRequest::factory()->for($lead)->create(['created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)]);
    $lead->notes()->save(tap(new Note(['body' => 'nota']), fn ($note) => $note->author()->associate(auth()->user())));

    Livewire::test($widget)->assertSuccessful();
})->with([
    CommercialStatsOverview::class,
    ContentStatsOverview::class,
    LeadsPerMonthChart::class,
    RequestsByTypeChart::class,
    LeadStatusChart::class,
    LeadSourcesChart::class,
    LatestRequestsWidget::class,
    FollowUpWidget::class,
    PendingLeadsWidget::class,
    RecentActivityWidget::class,
]);
