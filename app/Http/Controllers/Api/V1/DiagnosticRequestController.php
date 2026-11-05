<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDiagnosticRequestRequest;
use App\Http\Responses\ApiResponse;
use App\Models\DiagnosticRequest;
use App\Services\Leads\LeadIntakeService;
use Illuminate\Http\JsonResponse;

class DiagnosticRequestController extends Controller
{
    public function store(StoreDiagnosticRequestRequest $request, LeadIntakeService $intake): JsonResponse
    {
        return ApiResponse::submission($intake->submit(
            DiagnosticRequest::class,
            LeadSource::Diagnostic,
            $request->leadData(),
            $request->submissionAttributes(),
            $request->submissionContext(),
        ));
    }
}
