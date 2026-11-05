<?php

namespace Database\Factories;

use App\Enums\ContactSubject;
use App\Models\ContactMessage;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'subject' => ContactSubject::Project,
            'message' => fake()->paragraph(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'consent_at' => now(),
        ];
    }
}
