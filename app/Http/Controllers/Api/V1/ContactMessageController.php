<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactMessageRequest;
use App\Http\Responses\ApiResponse;
use App\Models\ContactMessage;
use App\Services\Leads\LeadIntakeService;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request, LeadIntakeService $intake): JsonResponse
    {
        return ApiResponse::submission($intake->submit(
            ContactMessage::class,
            LeadSource::Contact,
            $request->leadData(),
            $request->submissionAttributes(),
            $request->submissionContext(),
        ));
    }
}
