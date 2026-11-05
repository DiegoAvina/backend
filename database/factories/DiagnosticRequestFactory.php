<?php

namespace Database\Factories;

use App\Enums\ContactMethod;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosticRequest>
 */
class DiagnosticRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'solution_id' => Solution::factory(),
            'company' => fake()->company(),
            'contact_method' => ContactMethod::VideoCall,
            'availability' => null,
            'message' => fake()->paragraph(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'consent_at' => now(),
        ];
    }
}
