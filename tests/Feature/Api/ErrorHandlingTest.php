<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;

it('answers unknown api routes with the error format', function () {
    $this->getJson('/api/v1/no-existe')
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Recurso no encontrado.', 'errors' => []]);
});

it('answers json even when the client does not ask for it', function () {
    $this->get('/api/v1/products/no-existe')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('success', false);
});

it('hides internal errors and traces when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('/api/v1/_test/boom', fn () => throw new RuntimeException('SQLSTATE secret detail'));

    $response = $this->getJson('/api/v1/_test/boom');

    $response->assertStatus(500)
        ->assertExactJson(['success' => false, 'message' => 'No fue posible procesar la solicitud.', 'errors' => []]);

    expect($response->getContent())->not->toContain('SQLSTATE')
        ->not->toContain('trace')
        ->not->toContain('RuntimeException');
});

it('adds technical detail only when debug is on', function () {
    config(['app.debug' => true]);
    Route::get('/api/v1/_test/boom', fn () => throw new RuntimeException('detalle local'));

    $this->getJson('/api/v1/_test/boom')
        ->assertStatus(500)
        ->assertJsonPath('debug.detail', 'detalle local')
        ->assertJsonMissingPath('trace');
});

it('rate limits the public content api', function () {
    config(['services.frontend.content_rate_limit' => 2]);
    Product::factory()->create();

    $this->getJson('/api/v1/products')->assertOk();
    $this->getJson('/api/v1/products')->assertOk();
    $this->getJson('/api/v1/products')
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'Demasiadas solicitudes. Intenta de nuevo más tarde.');
});

describe('cors', function () {
    it('allows reads from the configured frontend origin', function () {
        $this->getJson('/api/v1/products', ['Origin' => 'http://localhost:3000'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    });

    it('does not allow other origins', function () {
        $allowed = $this->getJson('/api/v1/products', ['Origin' => 'https://otro-sitio.example'])
            ->headers->get('Access-Control-Allow-Origin');

        // Con un único origen permitido se responde siempre ese origen, así que
        // el navegador bloquea la lectura desde cualquier otro sitio.
        expect($allowed)->not->toBe('https://otro-sitio.example')
            ->not->toBe('*');
    });

    it('does not allow cross-origin writes from browsers', function () {
        $this->call('OPTIONS', '/api/v1/contact-messages', server: [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ])->assertHeader('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
    });
});
