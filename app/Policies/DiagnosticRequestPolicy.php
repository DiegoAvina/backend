<?php

namespace App\Policies;

class DiagnosticRequestPolicy extends CommercialPolicy
{
    protected function module(): string
    {
        return 'diagnostic_requests';
    }
}
