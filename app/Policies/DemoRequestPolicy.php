<?php

namespace App\Policies;

class DemoRequestPolicy extends CommercialPolicy
{
    protected function module(): string
    {
        return 'demo_requests';
    }
}
