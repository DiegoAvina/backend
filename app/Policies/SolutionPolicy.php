<?php

namespace App\Policies;

class SolutionPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'solutions';
    }
}
