<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListServicesRequest;
use App\Http\Resources\V1\ServiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(ListServicesRequest $request): JsonResponse
    {
        $query = Service::query()
            ->published()
            ->when($request->validated('group'), fn (Builder $query, string $group) => $query->where('group', $group));

        return ApiResponse::paginated(
            ServiceResource::collection($request->applySort($query)->paginate($request->perPage())),
        );
    }

    public function show(string $slug): JsonResponse
    {
        $service = Service::query()->published()->where('slug', $slug)->firstOrFail();

        return ApiResponse::success(new ServiceResource($service));
    }
}
