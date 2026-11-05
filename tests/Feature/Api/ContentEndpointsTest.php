<?php

use App\Enums\CategoryType;
use App\Enums\ProductStatus;
use App\Enums\ServiceGroup;
use App\Models\Article;
use App\Models\Category;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;

describe('response format', function () {
    it('wraps lists with success, message, data and pagination meta', function () {
        Product::factory()->count(3)->create();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => [['slug', 'name', 'status', 'category']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3);
    });

    it('wraps a single resource with success, message and data', function () {
        $service = Service::factory()->create();

        $this->getJson("/api/v1/services/{$service->slug}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $service->slug)
            ->assertJsonMissingPath('meta');
    });

    it('never exposes internal columns', function (string $uri, string $model) {
        $model::factory()->create(['slug' => 'elemento-publico']);

        $item = $this->getJson($uri.'/elemento-publico')->assertOk()->json('data');

        expect($item)->not->toHaveKeys(['id', 'is_published', 'sort_order', 'category_id', 'created_at', 'deleted_at', 'author_id']);
    })->with([
        ['/api/v1/products', Product::class],
        ['/api/v1/services', Service::class],
        ['/api/v1/solutions', Solution::class],
        ['/api/v1/industries', Industry::class],
        ['/api/v1/articles', Article::class],
    ]);
});

describe('publication', function () {
    it('lists only published catalog content', function (string $uri, string $model) {
        $published = $model::factory()->create();
        $model::factory()->unpublished()->create();

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $published->slug);
    })->with([
        ['/api/v1/products', Product::class],
        ['/api/v1/services', Service::class],
        ['/api/v1/solutions', Solution::class],
        ['/api/v1/industries', Industry::class],
    ]);

    it('responds 404 for unpublished catalog content', function (string $uri, string $model) {
        $hidden = $model::factory()->unpublished()->create();

        $this->getJson("{$uri}/{$hidden->slug}")
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'Recurso no encontrado.', 'errors' => []]);
    })->with([
        ['/api/v1/products', Product::class],
        ['/api/v1/services', Service::class],
        ['/api/v1/solutions', Solution::class],
        ['/api/v1/industries', Industry::class],
    ]);

    it('hides draft, scheduled, archived and deleted articles', function () {
        $visible = Article::factory()->create();
        $draft = Article::factory()->draft()->create();
        $scheduled = Article::factory()->scheduled()->create();
        $archived = Article::factory()->archived()->create();
        $deleted = Article::factory()->create();
        $deleted->delete();

        $this->getJson('/api/v1/articles')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', $visible->slug);

        foreach ([$draft, $scheduled, $archived, $deleted] as $article) {
            $this->getJson("/api/v1/articles/{$article->slug}")->assertNotFound();
        }
    });

    it('links only published services and products from solutions and industries', function (string $model, string $uri) {
        $owner = $model::factory()->create();
        $owner->services()->attach([
            Service::factory()->create(['slug' => 'visible'])->id => ['sort_order' => 0],
            Service::factory()->unpublished()->create(['slug' => 'oculto'])->id => ['sort_order' => 1],
        ]);
        $owner->products()->attach(Product::factory()->unpublished()->create());

        $this->getJson("{$uri}/{$owner->slug}")
            ->assertOk()
            ->assertJsonPath('data.related_services', [['slug' => 'visible', 'name' => Service::query()->where('slug', 'visible')->value('name')]])
            ->assertJsonPath('data.related_products', []);
    })->with([
        [Solution::class, '/api/v1/solutions'],
        [Industry::class, '/api/v1/industries'],
    ]);

    it('rejects slugs with invalid characters', function () {
        $this->getJson('/api/v1/products/Inventarios_2')->assertNotFound()->assertJsonPath('success', false);
    });
});

describe('products', function () {
    it('shows a product with its category and status label', function () {
        $product = Product::factory()->status(ProductStatus::InDevelopment)->create();

        $this->getJson("/api/v1/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_development')
            ->assertJsonPath('data.status_label', 'En desarrollo')
            ->assertJsonPath('data.category.slug', $product->category->slug)
            ->assertJsonPath('data.modules', $product->modules);
    });

    it('filters by category, status and featured flag', function () {
        $operations = Category::factory()->create(['slug' => 'operaciones']);
        $match = Product::factory()->for($operations)->featured()->status(ProductStatus::Beta)->create();
        Product::factory()->for($operations)->status(ProductStatus::Beta)->create();
        Product::factory()->featured()->status(ProductStatus::Beta)->create();
        Product::factory()->for($operations)->featured()->create();

        $this->getJson('/api/v1/products?category=operaciones&status=beta&featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $match->slug);
    });

    it('returns an empty list for an unknown category', function () {
        Product::factory()->create();

        $this->getJson('/api/v1/products?category=no-existe')->assertOk()->assertJsonCount(0, 'data');
    });
});

describe('services', function () {
    it('filters by group', function () {
        $infra = Service::factory()->group(ServiceGroup::Infrastructure)->create();
        Service::factory()->group(ServiceGroup::Software)->create();

        $this->getJson('/api/v1/services?group=infrastructure')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $infra->slug)
            ->assertJsonPath('data.0.group_label', 'IT Infrastructure');
    });
});

describe('articles', function () {
    it('includes the body only in the detail', function () {
        $article = Article::factory()->create();

        $this->getJson('/api/v1/articles')->assertOk()->assertJsonMissingPath('data.0.body');
        $this->getJson("/api/v1/articles/{$article->slug}")->assertOk()->assertJsonPath('data.body', $article->body);
    });

    it('sorts by most recent publication by default and filters by category', function () {
        $guides = Category::factory()->forArticles()->create(['slug' => 'guias']);
        $old = Article::factory()->for($guides)->create(['published_at' => now()->subMonth()]);
        $new = Article::factory()->for($guides)->create(['published_at' => now()->subDay()]);
        Article::factory()->create();

        $this->getJson('/api/v1/articles?category=guias')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', $new->slug)
            ->assertJsonPath('data.1.slug', $old->slug);
    });
});

describe('categories', function () {
    it('lists categories filtered by type without pagination', function () {
        Category::factory()->create(['type' => CategoryType::Product, 'name' => 'Operaciones']);
        Category::factory()->forArticles()->create();

        $this->getJson('/api/v1/categories?type=product')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'product')
            ->assertJsonMissingPath('meta');
    });
});

describe('pagination and sorting', function () {
    it('paginates with per_page and page', function () {
        Product::factory()->count(5)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();

        $this->getJson('/api/v1/products?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('data.0.slug', Product::query()->where('sort_order', 2)->value('slug'));
    });

    it('sorts by a whitelisted column in both directions', function () {
        Product::factory()->create(['name' => 'Beta']);
        Product::factory()->create(['name' => 'Alfa']);
        Product::factory()->create(['name' => 'Gamma']);

        expect($this->getJson('/api/v1/products?sort=name')->json('data.*.name'))->toBe(['Alfa', 'Beta', 'Gamma'])
            ->and($this->getJson('/api/v1/products?sort=-name')->json('data.*.name'))->toBe(['Gamma', 'Beta', 'Alfa']);
    });

    it('rejects invalid listing parameters with 422', function (string $query, string $field) {
        $this->getJson('/api/v1/products?'.$query)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([$field]);
    })->with([
        'sort column not allowed' => ['sort=description', 'sort'],
        'sql in sort' => ['sort=name;drop table products', 'sort'],
        'per_page too large' => ['per_page=500', 'per_page'],
        'per_page zero' => ['per_page=0', 'per_page'],
        'invalid status' => ['status=sold_out', 'status'],
        'unknown parameter' => ['is_published=0', 'is_published'],
    ]);
});
