<?php

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Solution;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

it('creates every table of the domain', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with([
    'categories', 'products', 'services', 'solutions', 'industries', 'articles',
    'service_solution', 'product_solution', 'industry_service', 'industry_product',
    'leads', 'contact_messages', 'quote_requests', 'demo_requests', 'diagnostic_requests',
]);

it('stores privacy and idempotency metadata on every request table', function (string $table) {
    expect(Schema::hasColumns($table, [
        'lead_id', 'status', 'idempotency_key', 'ip_hash', 'user_agent', 'utm', 'consent_at', 'created_at',
    ]))->toBeTrue();
})->with(['contact_messages', 'quote_requests', 'demo_requests', 'diagnostic_requests']);

it('rejects duplicated slugs', function () {
    Product::factory()->create(['slug' => 'inventarios']);

    Product::factory()->create(['slug' => 'inventarios']);
})->throws(UniqueConstraintViolationException::class);

it('allows the same category slug in different types', function () {
    Category::factory()->create(['type' => CategoryType::Product, 'slug' => 'operaciones']);
    Category::factory()->create(['type' => CategoryType::Article, 'slug' => 'operaciones']);

    expect(Category::query()->where('slug', 'operaciones')->count())->toBe(2);
});

it('rejects a duplicated category slug within the same type', function () {
    Category::factory()->create(['type' => CategoryType::Product, 'slug' => 'operaciones']);
    Category::factory()->create(['type' => CategoryType::Product, 'slug' => 'operaciones']);
})->throws(UniqueConstraintViolationException::class);

it('rejects two leads with the same email', function () {
    Lead::factory()->create(['email' => 'ana@example.com']);
    Lead::factory()->create(['email' => 'ana@example.com']);
})->throws(UniqueConstraintViolationException::class);

it('deletes the requests of a lead when the lead is deleted', function () {
    $lead = Lead::factory()->create();
    ContactMessage::factory()->for($lead)->create();
    QuoteRequest::factory()->for($lead)->create();

    $lead->delete();

    expect(ContactMessage::query()->count())->toBe(0)
        ->and(QuoteRequest::query()->count())->toBe(0);
});

it('keeps a product when its category is deleted', function () {
    $product = Product::factory()->create();

    $product->category->delete();

    expect($product->fresh())->not->toBeNull()
        ->and($product->fresh()->category_id)->toBeNull();
});

it('keeps a quote request when its service is deleted', function () {
    $quote = QuoteRequest::factory()->create();

    $quote->service->delete();

    expect($quote->fresh()->service_id)->toBeNull();
});

it('removes pivot rows when a solution is deleted', function () {
    $solution = Solution::factory()->create();
    $solution->services()->attach(Service::factory()->create());

    $solution->delete();

    expect(DB::table('service_solution')->count())->toBe(0)
        ->and(Service::query()->count())->toBe(1);
});
