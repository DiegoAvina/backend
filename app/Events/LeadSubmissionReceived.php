<?php

namespace App\Events;

use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Llegó un prospecto nuevo o una solicitud desde el sitio.
 */
class LeadSubmissionReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead|ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest $record,
    ) {}
}
