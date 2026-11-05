<?php

namespace Database\Factories;

use App\Enums\CompanySize;
use App\Models\DemoRequest;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemoRequest>
 */
class DemoRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'product_id' => Product::factory(),
            'company' => fake()->company(),
            'company_size' => CompanySize::Small,
            'role' => fake()->jobTitle(),
            'message' => null,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'consent_at' => now(),
        ];
    }
}
