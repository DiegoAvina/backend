<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ContactMethod;
use App\Models\Solution;
use Illuminate\Validation\Rule;

class StoreDiagnosticRequestRequest extends StoreSubmissionRequest
{
    /** Valor del formulario cuando el prospecto pide orientación sin tema definido. */
    public const UNDEFINED_TOPIC = 'no-definido';

    protected function submissionRules(): array
    {
        return [
            'company' => ['required', 'string', 'min:2', 'max:160'],
            'topic' => [
                'required',
                'string',
                'max:180',
                Rule::when(
                    $this->input('topic') !== self::UNDEFINED_TOPIC,
                    [Rule::exists(Solution::class, 'slug')->where('is_published', true)],
                ),
            ],
            'contact_method' => ['required', Rule::enum(ContactMethod::class)],
            'availability' => ['nullable', 'string', 'max:300'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }

    public function submissionAttributes(): array
    {
        $topic = $this->validated('topic');

        return [
            'solution_id' => $topic === self::UNDEFINED_TOPIC
                ? null
                : Solution::query()->published()->where('slug', $topic)->value('id'),
            'company' => $this->validated('company'),
            'contact_method' => $this->validated('contact_method'),
            'availability' => $this->validated('availability'),
            'message' => $this->validated('message'),
        ];
    }
}
