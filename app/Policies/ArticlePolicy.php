<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Model;

class ArticlePolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'articles';
    }

    protected function isPublished(Model $record): bool
    {
        /** @var Article $record */
        return $record->status === ArticleStatus::Published;
    }
}
