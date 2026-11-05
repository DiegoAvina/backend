<?php

namespace Database\Factories;

use App\Models\Solution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Solution>
 */
class SolutionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'headline' => fake()->sentence(),
            'summary' => fake()->sentence(),
            'icon' => 'workflow',
            'signals' => [fake()->sentence()],
            'approach' => [['title' => fake()->words(2, true), 'description' => fake()->sentence()]],
            'outcomes' => [fake()->sentence()],
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
