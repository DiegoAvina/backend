<?php

namespace App\Models;

use App\Enums\ContactSubject;
use App\Models\Concerns\LeadSubmission;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['lead_id', 'subject', 'message', 'idempotency_key', 'ip_hash', 'user_agent', 'utm', 'consent_at'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory, LeadSubmission;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => ContactSubject::class,
        ];
    }
}
