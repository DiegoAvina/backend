<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Settings\SiteSettings;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Configuración pública del sitio. Solo claves marcadas como públicas en
     * `SettingKey`; un valor no capturado se devuelve como null.
     */
    public function public(SiteSettings $settings): JsonResponse
    {
        return ApiResponse::success($settings->publicValues());
    }
}
