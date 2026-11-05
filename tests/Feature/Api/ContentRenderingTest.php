<?php

use App\Enums\ProductStatus;
use App\Models\Article;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('renders markdown bodies as HTML without any hand-written HTML or unsafe links', function () {
    $product = Product::factory()->create([
        'body' => "## Módulos\n\n<script>alert(1)</script><img src=x onerror=alert(1)>\n\n[enlace](javascript:alert(1)) y **negritas**",
    ]);

    $html = $this->getJson("/api/v1/products/{$product->slug}")->assertOk()->json('data.body_html');

    expect($html)->toContain('<h2>Módulos</h2>', '<strong>negritas</strong>')
        ->not->toContain('<script', 'onerror', 'javascript:');
});

it('returns article bodies as HTML only in the detail', function () {
    $article = Article::factory()->create(['body' => 'Texto con *énfasis*.']);

    $this->getJson('/api/v1/articles')->assertJsonMissingPath('data.0.body_html');
    $this->getJson("/api/v1/articles/{$article->slug}")->assertJsonPath('data.body_html', "<p>Texto con <em>énfasis</em>.</p>\n");
});

it('returns images as absolute URLs', function () {
    Storage::fake('public');
    $product = Product::factory()->create(['image' => 'products/foto.webp']);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertJsonPath('data.image_url', Storage::disk('public')->url('products/foto.webp'))
        ->assertJsonMissingPath('data.image');
});

it('hides discontinued products from listings unless explicitly requested', function () {
    $active = Product::factory()->create();
    $discontinued = Product::factory()->status(ProductStatus::Discontinued)->create();

    expect($this->getJson('/api/v1/products')->json('data.*.slug'))->toBe([$active->slug])
        ->and($this->getJson('/api/v1/products?status=discontinued')->json('data.*.slug'))->toBe([$discontinued->slug]);

    $this->getJson("/api/v1/products/{$discontinued->slug}")->assertOk()->assertJsonPath('data.status_label', 'Descontinuado');
});

it('does not accept demo requests for discontinued products', function () {
    Product::factory()->status(ProductStatus::Discontinued)->create(['slug' => 'viejo']);

    $this->postJson('/api/v1/demo-requests', leadPayload([
        'product' => 'viejo',
        'company_size' => '1-10',
    ]), serviceHeaders())->assertUnprocessable()->assertJsonValidationErrors(['product']);
});

it('never exposes internal notes, assignees or status history through any public endpoint', function () {
    $seller = User::factory()->create(['name' => 'Vendedora Secreta']);
    $lead = Lead::factory()->create();
    $lead->forceFill(['assigned_to' => $seller->id])->save();
    $quote = QuoteRequest::factory()->for($lead)->create();
    $note = new Note(['body' => 'NOTA-INTERNA-CONFIDENCIAL']);
    $note->author()->associate($seller);
    $quote->notes()->save($note);

    $public = collect(['/api/v1/products', '/api/v1/services', '/api/v1/solutions', '/api/v1/industries', '/api/v1/articles', '/api/v1/categories', '/api/v1/settings/public'])
        ->map(fn (string $uri) => $this->getJson($uri)->assertOk()->getContent())
        ->implode('');

    expect($public)->not->toContain('NOTA-INTERNA-CONFIDENCIAL', 'Vendedora Secreta', $lead->email, 'assigned_to', 'status_changes');
});
