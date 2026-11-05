<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;
use Database\Seeders\ContentSeeder;

/**
 * @return list<array<string, mixed>>
 */
function seedData(string $name): array
{
    return json_decode((string) file_get_contents(database_path("seeders/data/{$name}.json")), true);
}

it('seeds exactly the approved content of phase 3', function () {
    $this->seed(ContentSeeder::class);

    expect(Product::query()->count())->toBe(count(seedData('products')))
        ->and(Service::query()->count())->toBe(count(seedData('services')))
        ->and(Solution::query()->count())->toBe(count(seedData('solutions')))
        ->and(Industry::query()->count())->toBe(count(seedData('industries')))
        ->and(Category::query()->count())->toBe(collect(seedData('products'))->pluck('category')->unique()->count());

    $first = seedData('products')[0];
    $product = Product::query()->where('slug', $first['slug'])->firstOrFail();

    expect($product->name)->toBe($first['name'])
        ->and($product->description)->toBe($first['description'])
        ->and($product->modules)->toBe($first['modules'])
        ->and($product->category->name)->toBe($first['category']);
});

it('keeps every product in development as declared in the content', function () {
    $this->seed(ContentSeeder::class);

    expect(Product::query()->pluck('status')->unique()->all())->toBe([ProductStatus::InDevelopment]);
});

it('links related services and products in the declared order', function () {
    $this->seed(ContentSeeder::class);

    foreach (seedData('solutions') as $item) {
        $solution = Solution::query()->where('slug', $item['slug'])->firstOrFail();

        expect($solution->services->pluck('slug')->all())->toBe($item['relatedServices'])
            ->and($solution->products->pluck('slug')->all())->toBe($item['relatedProducts']);
    }
});

it('can run twice without duplicating content', function () {
    $this->seed(ContentSeeder::class);
    $this->seed(ContentSeeder::class);

    expect(Product::query()->count())->toBe(count(seedData('products')))
        ->and(DB::table('industry_service')->count())
        ->toBe(collect(seedData('industries'))->sum(fn (array $item) => count($item['relatedServices'])));
});

it('does not create articles nor users', function () {
    $this->seed();

    expect(DB::table('articles')->count())->toBe(0)
        ->and(DB::table('users')->count())->toBe(0);
});
