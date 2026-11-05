<?php

namespace App\Policies;

class IndustryPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'industries';
    }
}
