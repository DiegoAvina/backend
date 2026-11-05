<?php

namespace App\Policies;

class ProductPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'products';
    }
}
