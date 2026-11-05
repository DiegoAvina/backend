<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CategoryType;
use App\Http\Requests\Api\V1\Concerns\RejectsUnknownQueryParameters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Las categorías son pocas: se devuelven completas, sin paginación.
 */
class ListCategoriesRequest extends FormRequest
{
    use RejectsUnknownQueryParameters;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(CategoryType::class)],
        ];
    }
}
