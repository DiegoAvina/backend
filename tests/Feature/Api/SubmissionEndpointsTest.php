<?php

use App\Enums\LeadSource;
use App\Enums\RequestStatus;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Solution;
use App\Services\Leads\SubmissionContext;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Service::factory()->create(['slug' => 'desarrollo-web']);
    Product::factory()->create(['slug' => 'inventarios']);
    Solution::factory()->create(['slug' => 'automatizacion']);
});

/**
 * Endpoint, modelo, origen y datos válidos de cada formulario.
 *
 * @return array<string, array{string, class-string, LeadSource, array<string, mixed>}>
 */
function submissionForms(): array
{
    return [
        'contacto' => ['/api/v1/contact-messages', ContactMessage::class, LeadSource::Contact, [
            'subject' => 'proyecto',
            'message' => 'Queremos platicar sobre un sistema para nuestro almacén.',
        ]],
        'cotización' => ['/api/v1/quote-requests', QuoteRequest::class, LeadSource::Quote, [
            'service' => 'desarrollo-web',
            'budget' => '100k-300k',
            'timeline' => '1-3-meses',
            'message' => 'Necesitamos un sitio con catálogo y formulario de pedidos para mayoristas.',
        ]],
        'demo' => ['/api/v1/demo-requests', DemoRequest::class, LeadSource::Demo, [
            'product' => 'inventarios',
            'company_size' => '11-50',
            'role' => 'Gerente de operaciones',
        ]],
        'diagnóstico' => ['/api/v1/diagnostic-requests', DiagnosticRequest::class, LeadSource::Diagnostic, [
            'topic' => 'automatizacion',
            'contact_method' => 'videollamada',
            'availability' => 'Martes y jueves por la mañana',
            'message' => 'Capturamos pedidos a mano en tres sistemas distintos.',
        ]],
    ];
}

describe('storing', function () {
    it('stores the request and its lead', function (string $uri, string $model, LeadSource $source, array $fields) {
        $response = $this->postJson($uri, leadPayload($fields), serviceHeaders());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Solicitud registrada.')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonMissingPath('data.email');

        $record = $model::query()->sole();
        $lead = Lead::query()->sole();

        expect($record->lead->is($lead))->toBeTrue()
            ->and($record->status)->toBe(RequestStatus::New)
            ->and($record->consent_at)->not->toBeNull()
            ->and($record->user_agent)->toBe('Mozilla/5.0 (Pruebas)')
            ->and($record->ip_hash)->toBe(SubmissionContext::hashIp('203.0.113.10'))
            ->and($lead->source)->toBe($source)
            ->and($lead->email)->toBe('ana.perez@example.com');
    })->with(submissionForms());

    it('links the request with the selected catalog item', function () {
        $this->postJson('/api/v1/quote-requests', leadPayload(submissionForms()['cotización'][3]), serviceHeaders())->assertCreated();
        $this->postJson('/api/v1/demo-requests', leadPayload(submissionForms()['demo'][3]), serviceHeaders())->assertCreated();
        $this->postJson('/api/v1/diagnostic-requests', leadPayload(submissionForms()['diagnóstico'][3]), serviceHeaders())->assertCreated();

        expect(QuoteRequest::query()->sole()->service->slug)->toBe('desarrollo-web')
            ->and(DemoRequest::query()->sole()->product->slug)->toBe('inventarios')
            ->and(DiagnosticRequest::query()->sole()->solution->slug)->toBe('automatizacion');
    });

    it('accepts an undefined service or topic', function () {
        $quote = [...submissionForms()['cotización'][3], 'service' => 'no-definido'];
        $diagnostic = [...submissionForms()['diagnóstico'][3], 'topic' => 'no-definido'];

        $this->postJson('/api/v1/quote-requests', leadPayload($quote), serviceHeaders())->assertCreated();
        $this->postJson('/api/v1/diagnostic-requests', leadPayload($diagnostic), serviceHeaders())->assertCreated();

        expect(QuoteRequest::query()->sole()->service_id)->toBeNull()
            ->and(DiagnosticRequest::query()->sole()->solution_id)->toBeNull();
    });

    it('captures a general lead without a request', function () {
        $this->postJson('/api/v1/leads', leadPayload(['role' => 'Directora']), serviceHeaders())
            ->assertCreated();

        expect(Lead::query()->sole())
            ->source->toBe(LeadSource::General)
            ->role->toBe('Directora');
    });

    it('reuses the lead of a known email and updates its contact data', function () {
        $this->postJson('/api/v1/contact-messages', leadPayload(submissionForms()['contacto'][3]), serviceHeaders())->assertCreated();

        $this->postJson('/api/v1/quote-requests', leadPayload([
            ...submissionForms()['cotización'][3],
            'email' => 'ANA.PEREZ@example.com',
            'phone' => '',
            'company' => 'Nueva Empresa',
        ]), serviceHeaders())->assertCreated();

        $lead = Lead::query()->sole();

        expect($lead->company)->toBe('Nueva Empresa')
            ->and($lead->phone)->toBe('+525512345678')
            ->and($lead->source)->toBe(LeadSource::Contact)
            ->and($lead->contactMessages)->toHaveCount(1)
            ->and($lead->quoteRequests)->toHaveCount(1);
    });

    it('does not duplicate a general lead', function () {
        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders())->assertCreated();
        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders())->assertOk();

        expect(Lead::query()->count())->toBe(1);
    });
});

describe('normalization and sanitization', function () {
    it('normalizes mexican phone numbers to E.164', function (string $input) {
        $this->postJson('/api/v1/leads', leadPayload(['phone' => $input]), serviceHeaders())->assertCreated();

        expect(Lead::query()->sole()->phone)->toBe('+525512345678');
    })->with(['5512345678', '55 1234 5678', '(55) 1234-5678', '+52 55 1234 5678', '525512345678', '55.1234.5678']);

    it('lowercases the email and strips html and control characters', function () {
        $this->postJson('/api/v1/contact-messages', leadPayload([
            'name' => "<b>Ana</b> Pérez\u{0007}",
            'email' => 'Ana.Perez@Example.COM',
            'subject' => 'proyecto',
            'message' => "<script>alert('x')</script>Hola, queremos platicar sobre un proyecto.\nGracias.",
        ]), serviceHeaders())->assertCreated();

        $lead = Lead::query()->sole();

        expect($lead->name)->toBe('Ana Pérez')
            ->and($lead->email)->toBe('ana.perez@example.com')
            ->and(ContactMessage::query()->sole()->message)->toBe("alert('x')Hola, queremos platicar sobre un proyecto.\nGracias.");
    });

    it('stores only known utm keys', function () {
        $this->postJson('/api/v1/leads', leadPayload(['utm' => ['source' => 'google', 'campaign' => 'erp']]), serviceHeaders())
            ->assertCreated();

        expect(Lead::query()->sole()->utm)->toBe(['source' => 'google', 'campaign' => 'erp']);

        $this->postJson('/api/v1/leads', leadPayload(['email' => 'otro@example.com', 'utm' => ['gclid' => 'abc']]), serviceHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['utm']);
    });

    it('never stores the visitor ip in clear text', function () {
        $this->postJson('/api/v1/contact-messages', leadPayload(submissionForms()['contacto'][3]), serviceHeaders())->assertCreated();

        $rows = json_encode([DB::table('leads')->get(), DB::table('contact_messages')->get()]);

        expect($rows)->not->toContain('203.0.113.10');
    });
});

describe('validation', function () {
    it('returns errors with the standard format', function () {
        $this->postJson('/api/v1/contact-messages', [], serviceHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Los datos enviados no son válidos.')
            ->assertJsonValidationErrors(['name', 'email', 'consent', 'subject', 'message']);

        expect(Lead::query()->count())->toBe(0);
    });

    it('rejects invalid fields', function (string $uri, array $fields, string $error) {
        $form = collect(submissionForms())->first(fn (array $form) => $form[0] === $uri);

        $this->postJson($uri, leadPayload([...$form[3], ...$fields]), serviceHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$error]);

        expect($form[1]::query()->count())->toBe(0);
    })->with([
        'invalid email' => ['/api/v1/contact-messages', ['email' => 'ana@'], 'email'],
        'email too long' => ['/api/v1/contact-messages', ['email' => str_repeat('a', 150).'@example.com'], 'email'],
        'short name' => ['/api/v1/contact-messages', ['name' => 'A'], 'name'],
        'long name' => ['/api/v1/contact-messages', ['name' => str_repeat('a', 121)], 'name'],
        'invalid phone' => ['/api/v1/contact-messages', ['phone' => '12345'], 'phone'],
        'foreign phone' => ['/api/v1/contact-messages', ['phone' => '+1 415 555 0100'], 'phone'],
        'no consent' => ['/api/v1/contact-messages', ['consent' => false], 'consent'],
        'unknown subject' => ['/api/v1/contact-messages', ['subject' => 'ventas'], 'subject'],
        'short message' => ['/api/v1/contact-messages', ['message' => 'Hola'], 'message'],
        'long message' => ['/api/v1/contact-messages', ['message' => str_repeat('a', 2001)], 'message'],
        'html-only message' => ['/api/v1/contact-messages', ['message' => '<p></p><br>'], 'message'],
        'honeypot filled' => ['/api/v1/contact-messages', ['website' => 'https://spam.example'], 'website'],
        'unknown field' => ['/api/v1/contact-messages', ['is_admin' => true], 'is_admin'],
        'status injection' => ['/api/v1/contact-messages', ['status' => 'closed'], 'status'],
        'quote without company' => ['/api/v1/quote-requests', ['company' => ''], 'company'],
        'unknown service' => ['/api/v1/quote-requests', ['service' => 'no-existe'], 'service'],
        'invalid budget' => ['/api/v1/quote-requests', ['budget' => 'mucho'], 'budget'],
        'invalid timeline' => ['/api/v1/quote-requests', ['timeline' => 'ayer'], 'timeline'],
        'unknown product' => ['/api/v1/demo-requests', ['product' => 'no-existe'], 'product'],
        'invalid company size' => ['/api/v1/demo-requests', ['company_size' => '1000'], 'company_size'],
        'unknown topic' => ['/api/v1/diagnostic-requests', ['topic' => 'no-existe'], 'topic'],
        'invalid contact method' => ['/api/v1/diagnostic-requests', ['contact_method' => 'fax'], 'contact_method'],
    ]);

    it('rejects catalog items that are not published', function () {
        Product::factory()->unpublished()->create(['slug' => 'oculto']);

        $this->postJson('/api/v1/demo-requests', leadPayload([...submissionForms()['demo'][3], 'product' => 'oculto']), serviceHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product']);
    });

    it('answers validation messages in spanish', function () {
        $this->postJson('/api/v1/leads', leadPayload(['phone' => '123']), serviceHeaders())
            ->assertJsonPath('errors.phone.0', 'Ingresa un teléfono de 10 dígitos.');
    });
});

describe('idempotency', function () {
    it('returns the original record when a request is retried with the same key', function (string $uri, string $model, LeadSource $source, array $fields) {
        $headers = serviceHeaders(['Idempotency-Key' => 'b6f2c1d0-9a4e-4c4a-8f1e-1234567890ab']);

        $first = $this->postJson($uri, leadPayload($fields), $headers)->assertCreated();
        $retry = $this->postJson($uri, leadPayload($fields), $headers)->assertOk();

        $retry->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'));

        expect($model::query()->count())->toBe(1)
            ->and(Lead::query()->count())->toBe(1);
    })->with(submissionForms());

    it('creates separate records for different keys', function () {
        $fields = leadPayload(submissionForms()['contacto'][3]);

        $this->postJson('/api/v1/contact-messages', $fields, serviceHeaders(['Idempotency-Key' => str_repeat('a', 32)]))->assertCreated();
        $this->postJson('/api/v1/contact-messages', $fields, serviceHeaders(['Idempotency-Key' => str_repeat('b', 32)]))->assertCreated();

        expect(ContactMessage::query()->count())->toBe(2);
    });

    it('rejects malformed idempotency keys', function (string $key) {
        $this->postJson('/api/v1/contact-messages', leadPayload(submissionForms()['contacto'][3]), serviceHeaders(['Idempotency-Key' => $key]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key']);
    })->with(['corta', 'contiene espacios y más texto', str_repeat('x', 101)]);
});

describe('access control', function () {
    it('rejects requests without a valid service key', function (array $headers) {
        $this->postJson('/api/v1/contact-messages', leadPayload(submissionForms()['contacto'][3]), $headers)
            ->assertUnauthorized()
            ->assertExactJson(['success' => false, 'message' => 'No autorizado.', 'errors' => []]);

        expect(Lead::query()->count())->toBe(0);
    })->with([
        'missing key' => [['Accept' => 'application/json']],
        'wrong key' => [['Accept' => 'application/json', 'X-Service-Key' => 'otra-llave']],
    ]);

    it('rejects every write when no service key is configured', function () {
        config(['services.frontend.service_key' => null]);

        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders())->assertUnauthorized();
    });

    it('does not expose any listing of leads or requests', function (string $method, string $uri, int $status) {
        Lead::factory()->create();

        $this->json($method, $uri, [], serviceHeaders())
            ->assertStatus($status)
            ->assertJsonPath('success', false)
            ->assertJsonMissing(['email']);
    })->with([
        ['GET', '/api/v1/leads', 405],
        ['GET', '/api/v1/contact-messages', 405],
        ['GET', '/api/v1/quote-requests', 405],
        ['GET', '/api/v1/leads/1', 404],
        ['DELETE', '/api/v1/leads/1', 404],
    ]);
});

describe('rate limiting', function () {
    it('limits submissions per visitor ip', function () {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/leads', leadPayload(['email' => "persona{$attempt}@example.com"]), serviceHeaders())->assertCreated();
        }

        $this->postJson('/api/v1/leads', leadPayload(['email' => 'persona6@example.com']), serviceHeaders())
            ->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertHeader('Retry-After');

        $this->postJson('/api/v1/leads', leadPayload(['email' => 'persona7@example.com']), serviceHeaders(['X-Client-IP' => '198.51.100.20']))
            ->assertCreated();
    });

    it('limits submissions per email even from different ips', function () {
        foreach (range(1, 10) as $attempt) {
            $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders(['X-Client-IP' => "198.51.100.{$attempt}"]))->assertSuccessful();
        }

        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders(['X-Client-IP' => '198.51.100.200']))
            ->assertTooManyRequests();
    });

    it('ignores a forged client ip header without the service key', function () {
        foreach (range(1, 3) as $attempt) {
            $this->postJson('/api/v1/leads', leadPayload(), ['X-Client-IP' => "198.51.100.{$attempt}"])->assertUnauthorized();
        }
    });
});
