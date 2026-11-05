<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListArticlesRequest;
use App\Http\Resources\V1\ArticleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function index(ListArticlesRequest $request): JsonResponse
    {
        $query = Article::query()
            ->published()
            ->with(['category', 'author'])
            ->when($request->validated('category'), fn (Builder $query, string $slug) => $query->whereHas(
                'category',
                fn (Builder $category) => $category->where('type', CategoryType::Article)->where('slug', $slug),
            ));

        return ApiResponse::paginated(
            ArticleResource::collection($request->applySort($query)->paginate($request->perPage())),
        );
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::query()->published()->with(['category', 'author'])->where('slug', $slug)->firstOrFail();

        return ApiResponse::success(new ArticleResource($article));
    }
}
