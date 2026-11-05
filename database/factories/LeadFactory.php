<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+52'.fake()->numerify('##########'),
            'company' => fake()->company(),
            'role' => null,
            'source' => LeadSource::Contact,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'consent_at' => now(),
            'last_submitted_at' => now(),
        ];
    }
}
