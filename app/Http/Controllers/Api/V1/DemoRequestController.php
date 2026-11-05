<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDemoRequestRequest;
use App\Http\Responses\ApiResponse;
use App\Models\DemoRequest;
use App\Services\Leads\LeadIntakeService;
use Illuminate\Http\JsonResponse;

class DemoRequestController extends Controller
{
    public function store(StoreDemoRequestRequest $request, LeadIntakeService $intake): JsonResponse
    {
        return ApiResponse::submission($intake->submit(
            DemoRequest::class,
            LeadSource::Demo,
            $request->leadData(),
            $request->submissionAttributes(),
            $request->submissionContext(),
        ));
    }
}
