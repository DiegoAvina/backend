<?php

namespace App\Policies;

class ContactMessagePolicy extends CommercialPolicy
{
    protected function module(): string
    {
        return 'contact_messages';
    }
}
