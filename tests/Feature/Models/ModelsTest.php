<?php

use App\Enums\ArticleStatus;
use App\Enums\BudgetRange;
use App\Enums\ProductStatus;
use App\Enums\RequestStatus;
use App\Enums\ServiceGroup;
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
use Illuminate\Database\Eloquent\MassAssignmentException;

describe('casts', function () {
    it('casts product status and JSON fields', function () {
        $product = Product::factory()->create([
            'status' => 'beta',
            'features' => ['Multialmacén'],
            'modules' => [['title' => 'Catálogo', 'description' => 'Productos']],
        ])->fresh();

        expect($product->status)->toBe(ProductStatus::Beta)
            ->and($product->features)->toBe(['Multialmacén'])
            ->and($product->modules[0]['title'])->toBe('Catálogo')
            ->and($product->is_published)->toBeTrue();
    });

    it('casts service group and request enums', function () {
        expect(Service::factory()->create()->fresh()->group)->toBe(ServiceGroup::Software)
            ->and(QuoteRequest::factory()->create()->fresh()->budget_range)->toBe(BudgetRange::ToBeDefined);
    });

    it('starts every request with the "new" status', function (string $model) {
        $record = $model::factory()->create()->fresh();

        expect($record->status)->toBe(RequestStatus::New);
    })->with([ContactMessage::class, QuoteRequest::class, DemoRequest::class, DiagnosticRequest::class]);
});

describe('mass assignment', function () {
    it('does not allow assigning the status of a request from input', function () {
        $lead = Lead::factory()->create();

        new ContactMessage(['lead_id' => $lead->id, 'status' => 'closed']);
    })->throws(MassAssignmentException::class);

    it('does not allow assigning the status of a lead from input', function () {
        new Lead(['status' => 'closed']);
    })->throws(MassAssignmentException::class);
});

describe('serialization', function () {
    it('never serializes technical metadata of a request', function () {
        $array = ContactMessage::factory()->create(['idempotency_key' => 'abc123abc123abc123'])->toArray();

        expect($array)->not->toHaveKeys(['ip_hash', 'user_agent', 'idempotency_key']);
    });

    it('never serializes the ip hash of a lead', function () {
        expect(Lead::factory()->create()->toArray())->not->toHaveKey('ip_hash');
    });
});

describe('relations', function () {
    it('links products with their category', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        expect($product->category->is($category))->toBeTrue()
            ->and($category->products)->toHaveCount(1);
    });

    it('links solutions with services and products in editorial order', function () {
        $solution = Solution::factory()->create();
        [$first, $second] = Service::factory()->count(2)->create();
        $product = Product::factory()->create();

        $solution->services()->attach([$second->id => ['sort_order' => 1], $first->id => ['sort_order' => 0]]);
        $solution->products()->attach($product);

        expect($solution->services->pluck('id')->all())->toBe([$first->id, $second->id])
            ->and($solution->products->first()->is($product))->toBeTrue()
            ->and($first->solutions)->toHaveCount(1)
            ->and($product->solutions)->toHaveCount(1);
    });

    it('links industries with services and products in editorial order', function () {
        $industry = Industry::factory()->create();
        [$first, $second] = Service::factory()->count(2)->create();
        $product = Product::factory()->create();

        $industry->services()->attach([$second->id => ['sort_order' => 1], $first->id => ['sort_order' => 0]]);
        $industry->products()->attach($product);

        expect($industry->services->pluck('id')->all())->toBe([$first->id, $second->id])
            ->and($industry->products->first()->is($product))->toBeTrue()
            ->and($first->industries)->toHaveCount(1)
            ->and($product->industries)->toHaveCount(1);
    });

    it('links requests with their lead and catalog item', function () {
        $quote = QuoteRequest::factory()->create();
        $demo = DemoRequest::factory()->create();
        $diagnostic = DiagnosticRequest::factory()->create();

        expect($quote->lead)->toBeInstanceOf(Lead::class)
            ->and($quote->service)->toBeInstanceOf(Service::class)
            ->and($demo->product)->toBeInstanceOf(Product::class)
            ->and($diagnostic->solution)->toBeInstanceOf(Solution::class)
            ->and($quote->lead->quoteRequests)->toHaveCount(1);
    });

    it('links articles with category and author', function () {
        $author = User::factory()->create();
        $article = Article::factory()->for($author, 'author')->create();

        expect($article->author->is($author))->toBeTrue()
            ->and($article->category->articles)->toHaveCount(1);
    });
});

describe('publication scopes', function () {
    it('returns only published catalog content', function (string $model) {
        $model::factory()->create();
        $model::factory()->unpublished()->create();

        expect($model::query()->published()->count())->toBe(1);
    })->with([Product::class, Service::class, Solution::class, Industry::class]);

    it('returns only published articles whose date has arrived', function () {
        $visible = Article::factory()->create();
        Article::factory()->draft()->create();
        Article::factory()->scheduled()->create();
        Article::factory()->archived()->create();
        Article::factory()->create()->delete();

        expect(Article::query()->published()->pluck('id')->all())->toBe([$visible->id])
            ->and($visible->status)->toBe(ArticleStatus::Published);
    });
});
