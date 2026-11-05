<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListContentRequest;
use App\Http\Resources\V1\IndustryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Industry;
use Illuminate\Http\JsonResponse;

class IndustryController extends Controller
{
    public function index(ListContentRequest $request): JsonResponse
    {
        $query = Industry::query()->published()->with($this->publishedRelations());

        return ApiResponse::paginated(
            IndustryResource::collection($request->applySort($query)->paginate($request->perPage())),
        );
    }

    public function show(string $slug): JsonResponse
    {
        $industry = Industry::query()->published()->with($this->publishedRelations())->where('slug', $slug)->firstOrFail();

        return ApiResponse::success(new IndustryResource($industry));
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
