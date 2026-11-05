<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Contenido público inicial del sitio.
 *
 * Los archivos `database/seeders/data/*.json` se exportaron sin cambios desde
 * `frontend/src/data` (fase 3), de modo que el sitio muestre exactamente los
 * mismos textos aprobados. No se inventa contenido: no hay artículos aún.
 *
 * Es idempotente: actualiza por slug y nunca elimina registros, así que se
 * puede volver a ejecutar sin perder ediciones hechas fuera de estos campos.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $products = $this->seedProducts($this->load('products'));
            $services = $this->seedServices($this->load('services'));
            $this->seedSolutions($this->load('solutions'), $services, $products);
            $this->seedIndustries($this->load('industries'), $services, $products);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int> slug => id
     */
    private function seedProducts(array $items): array
    {
        $ids = [];
        $categoryOrder = 0;

        foreach ($items as $position => $item) {
            $categoryName = (string) $item['category'];
            $category = Category::query()->firstOrCreate(
                ['type' => CategoryType::Product, 'slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $categoryOrder++],
            );

            $product = Product::query()->updateOrCreate(['slug' => $item['slug']], [
                'category_id' => $category->id,
                'name' => $item['name'],
                'tagline' => $item['tagline'],
                'description' => $item['description'],
                'audience' => $item['audience'] ?? null,
                'status' => $item['status'],
                'icon' => $item['icon'] ?? null,
                'features' => $item['features'] ?? [],
                'problems' => $item['problems'] ?? [],
                'modules' => $item['modules'] ?? [],
                'is_featured' => (bool) ($item['featured'] ?? false),
                'is_published' => true,
                'sort_order' => $position,
            ]);

            $ids[$product->slug] = $product->id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int> slug => id
     */
    private function seedServices(array $items): array
    {
        $ids = [];

        foreach ($items as $position => $item) {
            $service = Service::query()->updateOrCreate(['slug' => $item['slug']], [
                'name' => $item['name'],
                'group' => $item['group'],
                'summary' => $item['summary'],
                'headline' => $item['headline'] ?? null,
                'description' => $item['description'] ?? null,
                'icon' => $item['icon'] ?? null,
                'benefits' => $item['benefits'] ?? [],
                'includes' => $item['includes'] ?? [],
                'ideal_for' => $item['idealFor'] ?? [],
                'is_published' => true,
                'sort_order' => $position,
            ]);

            $ids[$service->slug] = $service->id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, int>  $services
     * @param  array<string, int>  $products
     */
    private function seedSolutions(array $items, array $services, array $products): void
    {
        foreach ($items as $position => $item) {
            $solution = Solution::query()->updateOrCreate(['slug' => $item['slug']], [
                'name' => $item['name'],
                'headline' => $item['headline'] ?? null,
                'summary' => $item['summary'],
                'icon' => $item['icon'] ?? null,
                'signals' => $item['signals'] ?? [],
                'approach' => $item['approach'] ?? [],
                'outcomes' => $item['outcomes'] ?? [],
                'is_published' => true,
                'sort_order' => $position,
            ]);

            $solution->services()->syncWithoutDetaching($this->pivot($item['relatedServices'] ?? [], $services, 'servicio'));
            $solution->products()->syncWithoutDetaching($this->pivot($item['relatedProducts'] ?? [], $products, 'producto'));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, int>  $services
     * @param  array<string, int>  $products
     */
    private function seedIndustries(array $items, array $services, array $products): void
    {
        foreach ($items as $position => $item) {
            $industry = Industry::query()->updateOrCreate(['slug' => $item['slug']], [
                'name' => $item['name'],
                'summary' => $item['summary'],
                'icon' => $item['icon'] ?? null,
                'challenges' => $item['challenges'] ?? [],
                'applications' => $item['applications'] ?? [],
                'is_published' => true,
                'sort_order' => $position,
            ]);

            $industry->services()->syncWithoutDetaching($this->pivot($item['relatedServices'] ?? [], $services, 'servicio'));
            $industry->products()->syncWithoutDetaching($this->pivot($item['relatedProducts'] ?? [], $products, 'producto'));
        }
    }

    /**
     * Convierte una lista de slugs relacionados en datos de tabla pivote,
     * conservando el orden. Falla si un slug no existe (contenido inconsistente).
     *
     * @param  list<string>  $slugs
     * @param  array<string, int>  $ids
     * @return array<int, array{sort_order: int}>
     */
    private function pivot(array $slugs, array $ids, string $label): array
    {
        $pivot = [];

        foreach (array_values($slugs) as $position => $slug) {
            if (! isset($ids[$slug])) {
                throw new RuntimeException("El {$label} relacionado [{$slug}] no existe en el contenido inicial.");
            }

            $pivot[$ids[$slug]] = ['sort_order' => $position];
        }

        return $pivot;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function load(string $name): array
    {
        $path = database_path("seeders/data/{$name}.json");
        $items = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($items) || ! array_is_list($items)) {
            throw new RuntimeException("El archivo [{$path}] no contiene una lista.");
        }

        return $items;
    }
}
