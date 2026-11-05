<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;

class ListProductsRequest extends ListContentRequest
{
    protected function filterRules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:140'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'featured' => ['sometimes', 'boolean'],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['sort_order', 'name', 'updated_at'];
    }
}
