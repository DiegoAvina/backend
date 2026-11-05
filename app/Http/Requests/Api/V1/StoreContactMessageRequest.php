<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ContactSubject;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends StoreSubmissionRequest
{
    protected function submissionRules(): array
    {
        return [
            'subject' => ['required', Rule::enum(ContactSubject::class)],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }

    public function submissionAttributes(): array
    {
        return [
            'subject' => $this->validated('subject'),
            'message' => $this->validated('message'),
        ];
    }
}
