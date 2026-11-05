<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\BudgetRange;
use App\Enums\ProjectTimeline;
use App\Models\Service;
use Illuminate\Validation\Rule;

class StoreQuoteRequestRequest extends StoreSubmissionRequest
{
    /** Valor del formulario cuando el prospecto aún no define el servicio. */
    public const UNDEFINED_SERVICE = 'no-definido';

    protected function submissionRules(): array
    {
        return [
            'company' => ['required', 'string', 'min:2', 'max:160'],
            'service' => [
                'required',
                'string',
                'max:180',
                Rule::when(
                    $this->input('service') !== self::UNDEFINED_SERVICE,
                    [Rule::exists(Service::class, 'slug')->where('is_published', true)],
                ),
            ],
            'budget' => ['required', Rule::enum(BudgetRange::class)],
            'timeline' => ['required', Rule::enum(ProjectTimeline::class)],
            'message' => ['required', 'string', 'min:30', 'max:3000'],
        ];
    }

    public function submissionAttributes(): array
    {
        $service = $this->validated('service');

        return [
            'service_id' => $service === self::UNDEFINED_SERVICE
                ? null
                : Service::query()->published()->where('slug', $service)->value('id'),
            'company' => $this->validated('company'),
            'budget_range' => $this->validated('budget'),
            'timeline' => $this->validated('timeline'),
            'message' => $this->validated('message'),
        ];
    }
}
