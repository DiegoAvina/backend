<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CompanySize;
use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreDemoRequestRequest extends StoreSubmissionRequest
{
    protected function submissionRules(): array
    {
        return [
            'company' => ['required', 'string', 'min:2', 'max:160'],
            'product' => [
                'required',
                'string',
                'max:180',
                Rule::exists(Product::class, 'slug')
                    ->where('is_published', true)
                    ->whereNot('status', ProductStatus::Discontinued->value),
            ],
            'company_size' => ['required', Rule::enum(CompanySize::class)],
            'role' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:1500'],
        ];
    }

    public function submissionAttributes(): array
    {
        return [
            'product_id' => Product::query()->published()->where('slug', $this->validated('product'))->value('id'),
            'company' => $this->validated('company'),
            'company_size' => $this->validated('company_size'),
            'role' => $this->validated('role'),
            'message' => $this->validated('message'),
        ];
    }
}
