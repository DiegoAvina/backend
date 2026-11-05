<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListProductsRequest;
use App\Http\Resources\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(ListProductsRequest $request): JsonResponse
    {
        $query = Product::query()
            ->published()
            ->with('category')
            ->when($request->validated('category'), fn (Builder $query, string $slug) => $query->whereHas(
                'category',
                fn (Builder $category) => $category->where('type', CategoryType::Product)->where('slug', $slug),
            ))
            // Los descontinuados solo se listan si se piden explícitamente.
            ->when(
                $request->validated('status'),
                fn (Builder $query, string $status) => $query->where('status', $status),
                fn (Builder $query) => $query->where('status', '!=', ProductStatus::Discontinued),
            )
            ->when($request->has('featured'), fn (Builder $query) => $query->where('is_featured', $request->boolean('featured')));

        return ApiResponse::paginated(
            ProductResource::collection($request->applySort($query)->paginate($request->perPage())),
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()->published()->with('category')->where('slug', $slug)->firstOrFail();

        return ApiResponse::success(new ProductResource($product));
    }
}
