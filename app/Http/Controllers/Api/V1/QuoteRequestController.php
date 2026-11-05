<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreQuoteRequestRequest;
use App\Http\Responses\ApiResponse;
use App\Models\QuoteRequest;
use App\Services\Leads\LeadIntakeService;
use Illuminate\Http\JsonResponse;

class QuoteRequestController extends Controller
{
    public function store(StoreQuoteRequestRequest $request, LeadIntakeService $intake): JsonResponse
    {
        return ApiResponse::submission($intake->submit(
            QuoteRequest::class,
            LeadSource::Quote,
            $request->leadData(),
            $request->submissionAttributes(),
            $request->submissionContext(),
        ));
    }
}
