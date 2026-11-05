<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListCategoriesRequest;
use App\Http\Resources\V1\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(ListCategoriesRequest $request): JsonResponse
    {
        $categories = Category::query()
            ->when($request->validated('type'), fn (Builder $query, string $type) => $query->where('type', $type))
            ->orderBy('type')
            ->ordered()
            ->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }
}
