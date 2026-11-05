<?php

namespace App\Policies;

class LeadPolicy extends CommercialPolicy
{
    protected function module(): string
    {
        return 'leads';
    }
}
