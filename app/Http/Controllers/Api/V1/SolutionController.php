<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListContentRequest;
use App\Http\Resources\V1\SolutionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Solution;
use Illuminate\Http\JsonResponse;

class SolutionController extends Controller
{
    public function index(ListContentRequest $request): JsonResponse
    {
        $query = Solution::query()->published()->with($this->publishedRelations());

        return ApiResponse::paginated(
            SolutionResource::collection($request->applySort($query)->paginate($request->perPage())),
        );
    }

    public function show(string $slug): JsonResponse
    {
        $solution = Solution::query()->published()->with($this->publishedRelations())->where('slug', $slug)->firstOrFail();

        return ApiResponse::success(new SolutionResource($solution));
    }

    /**
     * Solo se enlazan servicios y productos publicados.
     *
     * @return array<string, \Closure>
     */
    private function publishedRelations(): array
    {
        return [
            'services' => fn ($query) => $query->published(),
            'products' => fn ($query) => $query->published(),
        ];
    }
}
