<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLeadRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Leads\LeadIntakeService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, LeadIntakeService $intake): JsonResponse
    {
        return ApiResponse::submission(
            $intake->captureLead($request->leadData(), $request->submissionContext()),
        );
    }
}
