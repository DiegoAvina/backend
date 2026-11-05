<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->words(2, true));

        return [
            'type' => CategoryType::Product,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => null,
            'sort_order' => 0,
        ];
    }

    public function forArticles(): static
    {
        return $this->state(['type' => CategoryType::Article]);
    }
}
