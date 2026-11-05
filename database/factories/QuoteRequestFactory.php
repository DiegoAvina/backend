<?php

namespace Database\Factories;

use App\Enums\BudgetRange;
use App\Enums\ProjectTimeline;
use App\Models\Lead;
use App\Models\QuoteRequest;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteRequest>
 */
class QuoteRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'service_id' => Service::factory(),
            'company' => fake()->company(),
            'budget_range' => BudgetRange::ToBeDefined,
            'timeline' => ProjectTimeline::OneToThreeMonths,
            'message' => fake()->paragraph(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'consent_at' => now(),
        ];
    }
}
