<?php

namespace App\Policies;

class ServicePolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'services';
    }
}
