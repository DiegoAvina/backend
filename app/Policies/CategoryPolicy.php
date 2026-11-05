<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CategoryPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'categories';
    }

    public function publish(User $user, Model $record): bool
    {
        return false;
    }

    /**
     * Una categoría solo se elimina si ya no tiene productos ni artículos.
     */
    public function delete(User $user, Model $record): bool
    {
        /** @var Category $record */
        return $user->can('categories.delete')
            && ! $record->products()->exists()
            && ! $record->articles()->withTrashed()->exists();
    }
}
