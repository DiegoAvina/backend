<?php

use App\Enums\ArticleStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

describe('listing and searching', function () {
    it('lists and searches products', function () {
        actingAsRole(UserRole::Editor);
        $match = Product::factory()->create(['name' => 'Gestión de inventarios']);
        $other = Product::factory()->create(['name' => 'CRM comercial']);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$match, $other])
            ->searchTable('inventarios')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('filters products by status and publication', function () {
        actingAsRole(UserRole::Editor);
        $beta = Product::factory()->status(ProductStatus::Beta)->create();
        $hidden = Product::factory()->unpublished()->create();

        Livewire::test(ListProducts::class)
            ->filterTable('status', ProductStatus::Beta->value)
            ->assertCanSeeTableRecords([$beta])
            ->assertCanNotSeeTableRecords([$hidden])
            ->resetTableFilters()
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords([$hidden])
            ->assertCanNotSeeTableRecords([$beta]);
    });

    it('sorts by editorial order', function () {
        actingAsRole(UserRole::Editor);
        $second = Product::factory()->create(['sort_order' => 2]);
        $first = Product::factory()->create(['sort_order' => 1]);

        Livewire::test(ListProducts::class)->assertCanSeeTableRecords([$first, $second], inOrder: true);
    });
});

describe('creating and editing', function () {
    it('creates a product unpublished, even if the request tries to publish it', function () {
        actingAsRole(UserRole::Editor);
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Control de flotillas',
                'slug' => 'control-flotillas',
                'category_id' => $category->id,
                'status' => ProductStatus::InDevelopment->value,
                'tagline' => 'Ubicación y mantenimiento de unidades.',
                'description' => 'Seguimiento de vehículos y su mantenimiento.',
                // En el formulario, un repetidor simple guarda cada elemento como ['value' => …].
                'features' => [['value' => 'Rastreo'], ['value' => 'Mantenimiento preventivo']],
                'modules' => [['title' => 'Unidades', 'description' => 'Alta y baja de vehículos.']],
                'seo' => ['title' => 'Control de flotillas', 'description' => 'Software para flotillas.'],
                'sort_order' => 3,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'control-flotillas')->sole();

        expect($product->is_published)->toBeFalse()
            ->and($product->features)->toBe(['Rastreo', 'Mantenimiento preventivo'])
            ->and($product->seo['title'])->toBe('Control de flotillas')
            ->and(AuditLog::query()->where('event', 'created')->where('auditable_id', $product->id)->exists())->toBeTrue();
    });

    it('validates required fields, slug format and uniqueness', function () {
        actingAsRole(UserRole::Editor);
        Product::factory()->create(['slug' => 'existente']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => '', 'slug' => 'Con Espacios', 'tagline' => '', 'description' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'regex', 'tagline' => 'required', 'description' => 'required']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Otro', 'slug' => 'existente', 'status' => 'in_development', 'tagline' => 'x', 'description' => 'x'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    });

    it('edits a product and records the change', function () {
        actingAsRole(UserRole::Editor);
        $product = Product::factory()->create(['tagline' => 'Antes']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['tagline' => 'Después'])
            ->call('save')
            ->assertHasNoFormErrors();

        $log = AuditLog::query()->where('event', 'updated')->latest('id')->first();

        expect($product->fresh()->tagline)->toBe('Después')
            ->and($log->old_values)->toBe(['tagline' => 'Antes'])
            ->and($log->new_values)->toBe(['tagline' => 'Después']);
    });

    it('syncs related services of a solution', function () {
        actingAsRole(UserRole::Editor);
        $solution = Solution::factory()->create();
        $service = Service::factory()->create();

        Livewire::test(EditSolution::class, ['record' => $solution->getRouteKey()])
            ->fillForm(['services' => [$service->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($solution->services()->pluck('services.id')->all())->toBe([$service->id]);
    });
});

describe('publication', function () {
    it('publishes and unpublishes from the table, with audit trail', function () {
        actingAsRole(UserRole::Editor);
        $product = Product::factory()->unpublished()->create();

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('publish')->table($product))
            ->assertNotified();

        expect($product->fresh()->is_published)->toBeTrue()
            ->and(AuditLog::query()->where('event', 'published')->count())->toBe(1);

        Livewire::test(ListProducts::class)->callAction(TestAction::make('unpublish')->table($product));

        expect($product->fresh()->is_published)->toBeFalse()
            ->and(AuditLog::query()->where('event', 'unpublished')->count())->toBe(1);
    });

    it('publishes an article with the current date when it has none', function () {
        actingAsRole(UserRole::Editor);
        $article = Article::factory()->draft()->create();

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])->callAction('publish');

        expect($article->fresh())
            ->status->toBe(ArticleStatus::Published)
            ->published_at->not->toBeNull();
    });

    it('does not let a user without the publish permission publish', function () {
        actingAsRole(UserRole::Editor);
        Role::findByName(UserRole::Editor->value)->revokePermissionTo('products.publish');
        $product = Product::factory()->unpublished()->create();

        Livewire::test(ListProducts::class)->assertActionHidden(TestAction::make('publish')->table($product));

        expect($product->fresh()->is_published)->toBeFalse();
    });
});

describe('controlled deletion', function () {
    it('does not allow deleting published content', function () {
        actingAsRole(UserRole::Editor);
        $published = Product::factory()->create();
        $draft = Product::factory()->unpublished()->create();

        Livewire::test(ListProducts::class)
            ->assertActionHidden(TestAction::make('delete')->table($published))
            ->callAction(TestAction::make('delete')->table($draft));

        expect(Product::query()->pluck('id')->all())->toBe([$published->id]);
    });

    it('does not allow deleting a category in use', function () {
        actingAsRole(UserRole::Editor);
        $used = Product::factory()->create()->category;
        $empty = Category::factory()->create();

        Livewire::test(ListCategories::class)
            ->assertActionHidden(TestAction::make('delete')->table($used))
            ->callAction(TestAction::make('delete')->table($empty));

        expect(Category::query()->pluck('id')->all())->toBe([$used->id]);
    });

    it('soft deletes articles and allows restoring them', function () {
        actingAsRole(UserRole::Editor);
        $article = Article::factory()->draft()->create();

        Livewire::test(ListArticles::class)->callAction(TestAction::make('delete')->table($article));
        expect($article->fresh()->trashed())->toBeTrue();

        Livewire::test(ListArticles::class)
            ->filterTable('trashed', true)
            ->callAction(TestAction::make('restore')->table($article));
        expect($article->fresh()->trashed())->toBeFalse();
    });
});

describe('images', function () {
    beforeEach(fn () => Storage::fake('public'));

    it('stores an uploaded image with a random name on the public disk', function () {
        actingAsRole(UserRole::Editor);
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['image' => UploadedFile::fake()->image('Mi Foto Original.png', 800, 600)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $product->fresh()->image;

        expect($path)->toStartWith('products/')
            ->not->toContain('Mi Foto Original');
        Storage::disk('public')->assertExists($path);
    });

    it('rejects files that are not allowed images or are too large', function (UploadedFile $file) {
        actingAsRole(UserRole::Editor);
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['image' => $file])
            ->call('save')
            ->assertHasFormErrors(['image']);

        expect($product->fresh()->image)->toBeNull();
    })->with([
        'pdf' => fn () => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        'svg' => fn () => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        'php disguised' => fn () => UploadedFile::fake()->create('shell.php', 1, 'text/x-php'),
        'too large' => fn () => UploadedFile::fake()->image('enorme.jpg')->size(3000),
    ]);

    it('deletes the previous image when it is replaced', function () {
        actingAsRole(UserRole::Editor);
        Storage::disk('public')->put('products/anterior.png', 'x');
        $product = Product::factory()->create(['image' => 'products/anterior.png']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            // Como en el panel: se quita la imagen actual y se sube la nueva.
            ->fillForm(['image' => null])
            ->fillForm(['image' => [UploadedFile::fake()->image('nueva.png')]])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('products/anterior.png');
        Storage::disk('public')->assertExists($product->fresh()->image);
    });
});

describe('access by role', function () {
    it('lets every content role but no one else open the product list', function (UserRole $role, bool $allowed) {
        actingAsRole($role);

        $this->get(ProductResource::getUrl('index'))->assertStatus($allowed ? 200 : 403);
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Sales, true],
        [UserRole::Viewer, true],
    ]);

    it('gives read-only access to sales and viewer roles', function (UserRole $role) {
        actingAsRole($role);
        $product = Product::factory()->unpublished()->create();

        $this->get(ProductResource::getUrl('create'))->assertForbidden();
        $this->get(ProductResource::getUrl('edit', ['record' => $product]))->assertForbidden();

        Livewire::test(ListProducts::class)
            ->assertActionHidden(TestAction::make('edit')->table($product))
            ->assertActionHidden(TestAction::make('publish')->table($product))
            ->assertActionHidden(TestAction::make('delete')->table($product))
            ->assertActionHidden('create');
    })->with([UserRole::Sales, UserRole::Viewer]);

    it('renders every content list for an editor', function (string $page) {
        actingAsRole(UserRole::Editor);

        Livewire::test($page)->assertSuccessful();
    })->with([ListProducts::class, ListServices::class, ListArticles::class, ListCategories::class]);
});
