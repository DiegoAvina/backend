<?php

namespace App\Http\Requests\Api\V1;

class ListArticlesRequest extends ListContentRequest
{
    protected function filterRules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:140'],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['published_at', 'title'];
    }

    protected function defaultSort(): string
    {
        return '-published_at';
    }
}
