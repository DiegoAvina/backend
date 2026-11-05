<?php

namespace Database\Factories;

use App\Enums\ServiceGroup;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->words(3, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'group' => ServiceGroup::Software,
            'summary' => fake()->sentence(),
            'headline' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'icon' => 'code',
            'benefits' => [['title' => fake()->words(2, true), 'description' => fake()->sentence()]],
            'includes' => fake()->words(3),
            'ideal_for' => [fake()->sentence()],
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function group(ServiceGroup $group): static
    {
        return $this->state(['group' => $group]);
    }
}
