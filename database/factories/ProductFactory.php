<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'audience' => fake()->sentence(),
            'status' => ProductStatus::InDevelopment,
            'icon' => 'boxes',
            'features' => fake()->words(3),
            'problems' => [fake()->sentence()],
            'modules' => [['title' => fake()->words(2, true), 'description' => fake()->sentence()]],
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function status(ProductStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
