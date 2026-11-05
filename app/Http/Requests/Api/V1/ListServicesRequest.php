<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ServiceGroup;
use Illuminate\Validation\Rule;

class ListServicesRequest extends ListContentRequest
{
    protected function filterRules(): array
    {
        return [
            'group' => ['sometimes', Rule::enum(ServiceGroup::class)],
        ];
    }
}
