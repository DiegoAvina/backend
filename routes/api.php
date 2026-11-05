<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use App\Http\Controllers\Api\V1\DemoRequestController;
use App\Http\Controllers\Api\V1\DiagnosticRequestController;
use App\Http\Controllers\Api\V1\IndustryController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuoteRequestController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SolutionController;
use Illuminate\Support\Facades\Route;

/*
| API v1. Documentación: docs/06-api-reference.md
|
| - Lectura pública: sin autenticación, solo contenido publicado.
| - Escritura: solo el servidor de Next.js, con llave de servicio.
*/

Route::pattern('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware('throttle:content-api')->group(function (): void {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');

        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{slug}', [ServiceController::class, 'show'])->name('services.show');

        Route::get('solutions', [SolutionController::class, 'index'])->name('solutions.index');
        Route::get('solutions/{slug}', [SolutionController::class, 'show'])->name('solutions.show');

        Route::get('industries', [IndustryController::class, 'index'])->name('industries.index');
        Route::get('industries/{slug}', [IndustryController::class, 'show'])->name('industries.show');

        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
    });

    Route::middleware(['service.key', 'throttle:lead-submissions'])->group(function (): void {
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::post('contact-messages', [ContactMessageController::class, 'store'])->name('contact-messages.store');
        Route::post('quote-requests', [QuoteRequestController::class, 'store'])->name('quote-requests.store');
        Route::post('demo-requests', [DemoRequestController::class, 'store'])->name('demo-requests.store');
        Route::post('diagnostic-requests', [DiagnosticRequestController::class, 'store'])->name('diagnostic-requests.store');
    });
});
