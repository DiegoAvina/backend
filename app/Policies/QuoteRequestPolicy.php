<?php

namespace App\Policies;

class QuoteRequestPolicy extends CommercialPolicy
{
    protected function module(): string
    {
        return 'quote_requests';
    }
}
