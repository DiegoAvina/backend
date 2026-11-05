<?php

use App\Jobs\RevalidateFrontendContent;
use App\Models\Lead;
use App\Models\Product;
use App\Notifications\NewLeadSubmission;
use App\Services\Settings\SiteSettings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

describe('revalidation webhook', function () {
    beforeEach(function () {
        config([
            'services.frontend.revalidate_url' => 'https://frontend.test/api/revalidate',
            'services.frontend.revalidate_secret' => 'secreto-de-prueba',
        ]);
    });

    it('queues a revalidation when content changes', function () {
        Queue::fake();
        $product = Product::factory()->create();

        $product->update(['tagline' => 'Nueva frase']);

        Queue::assertPushed(RevalidateFrontendContent::class, fn ($job) => $job->tags === ['products', 'solutions', 'industries']);
    });

    it('signs the request with HMAC and a timestamp', function () {
        Http::fake(['frontend.test/*' => Http::response(['revalidated' => true])]);

        (new RevalidateFrontendContent(['products']))->handle();

        Http::assertSent(function (Request $request): bool {
            $timestamp = $request->header('X-Revalidate-Timestamp')[0];
            $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), 'secreto-de-prueba');

            return $request->url() === 'https://frontend.test/api/revalidate'
                && $request->header('X-Revalidate-Signature')[0] === $expected
                && abs(now()->getTimestamp() - (int) $timestamp) < 5
                && json_decode($request->body(), true) === ['tags' => ['products']];
        });
    });

    it('does nothing when the webhook is not configured', function () {
        config(['services.frontend.revalidate_url' => null]);
        Http::fake();

        (new RevalidateFrontendContent(['products']))->handle();

        Http::assertNothingSent();
    });

    it('fails so the queue retries when the frontend rejects it', function () {
        Http::fake(['frontend.test/*' => Http::response(['error' => 'Firma inválida'], 401)]);

        (new RevalidateFrontendContent(['products']))->handle();
    })->throws(RequestException::class);
});

describe('commercial notifications', function () {
    beforeEach(fn () => (new SettingsSeeder)->run());

    it('sends nothing while notifications are disabled (default)', function () {
        Notification::fake();
        config(['leads.notifications.recipients' => 'ventas@example.com']);

        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders())->assertCreated();

        Notification::assertNothingSent();
    });

    it('notifies the configured recipients when enabled', function () {
        Notification::fake();
        config(['leads.notifications.enabled' => true]);
        app(SiteSettings::class)->update(['notification_recipients' => 'ventas@example.com, direccion@example.com']);

        $this->postJson('/api/v1/quote-requests', leadPayload([
            'service' => 'no-definido',
            'budget' => 'por-definir',
            'timeline' => 'explorando',
            'message' => 'Queremos cotizar un sistema de inventarios para tres almacenes.',
        ]), serviceHeaders())->assertCreated();

        Notification::assertSentOnDemand(
            NewLeadSubmission::class,
            fn (NewLeadSubmission $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === ['ventas@example.com', 'direccion@example.com']
                && $notification->typeLabel() === 'Nueva solicitud de cotización',
        );
    });

    it('falls back to the environment recipients', function () {
        Notification::fake();
        config(['leads.notifications.enabled' => true, 'leads.notifications.recipients' => 'equipo@example.com']);

        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders())->assertCreated();

        Notification::assertSentOnDemandTimes(NewLeadSubmission::class, 1);
    });

    it('does not notify again on an idempotent retry', function () {
        Notification::fake();
        config(['leads.notifications.enabled' => true, 'leads.notifications.recipients' => 'equipo@example.com']);
        $headers = serviceHeaders(['Idempotency-Key' => str_repeat('a', 32)]);
        $payload = leadPayload(['subject' => 'proyecto', 'message' => 'Queremos platicar sobre un proyecto nuevo.']);

        $this->postJson('/api/v1/contact-messages', $payload, $headers)->assertCreated();
        $this->postJson('/api/v1/contact-messages', $payload, $headers)->assertOk();

        Notification::assertSentOnDemandTimes(NewLeadSubmission::class, 1);
    });

    it('keeps personal details out of the email besides name and company', function () {
        $lead = Lead::factory()->create(['name' => 'Ana', 'company' => 'Acme', 'phone' => '+525512345678']);

        $mail = (new NewLeadSubmission($lead))->toMail(new stdClass);
        $text = implode(' ', $mail->introLines).' '.implode(' ', $mail->outroLines);

        expect($text)->toContain('Ana', 'Acme')->not->toContain('+525512345678', $lead->email);
    });
});
